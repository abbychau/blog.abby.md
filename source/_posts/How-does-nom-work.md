---
title: How does nom v.5 work?
date: 2019-05-17 09:24:58
categories: Rust
tags: 
- nom 
- Rust 
- parser 
- json 
- nom
---


## What is nom?

`nom` is a parser combinators library written in Rust. Its goal is to provide tools to build safe parsers without compromising the speed or memory consumption. To that end, it uses extensively Rust's strong typing and memory safety to produce fast and correct parsers, and provides functions, macros and traits to abstract most of the error prone plumbing.

## Why this passage?

`nom` version 5 is going through a very large change to the lexical syntax to be used. The old way will not work any more. So this passage will tell and explain the new way.

## Hello Color

This is a hello world example for parser that we are going to parse Hex color like `#2F14DF`.

```Rust
extern crate nom;
use nom::{
  IResult,
  bytes::complete::{tag, take_while_m_n},
  combinator::map_res,
  sequence::tuple
};

#[derive(Debug,PartialEq)]
pub struct Color {
  pub red:   u8,
  pub green: u8,
  pub blue:  u8,
}

fn from_hex(input: &str) -> Result<u8, std::num::ParseIntError> {
  u8::from_str_radix(input, 16)
}

fn is_hex_digit(c: char) -> bool {
  c.is_digit(16)
}

fn hex_primary(input: &str) -> IResult<&str, u8> {
  map_res(
    take_while_m_n(2, 2, is_hex_digit),
    from_hex
  )(input)
}

fn hex_color(input: &str) -> IResult<&str, Color> {
  let (input, _) = tag("#")(input)?;
  let (input, (red, green, blue)) = tuple((hex_primary, hex_primary, hex_primary))(input)?;

  Ok((input, Color { red, green, blue }))
}

fn main() {}

#[test]
fn parse_color() {
  assert_eq!(hex_color("#2F14DF"), Ok(("", Color {
    red: 47,
    green: 20,
    blue: 223,
  })));
}
```

The combinator is writting in a very functional way.

```Rust
let (input, _) = tag("#")(input)?;
let (input, (red, green, blue)) = tuple((hex_primary, hex_primary, hex_primary))(input)?;

Ok((input, Color { red, green, blue }))
```

That, `tag("#")`, `tuple((hex_primary, hex_primary, hex_primary))` are functions.

## Parse Json

Then we come to the real world. They provided a very efficient Json parser as an example of nom.

Please find the example [here](https://github.com/Geal/nom/blob/master/examples/json.rs).


Revision, this is the defination or state-machine of Json.


```Rust
pub enum JsonValue {
  Str(String),
  Boolean(bool),
  Num(f64),
  Array(Vec<JsonValue>),
  Object(HashMap<String, JsonValue>),
}
```

The following are lexical elements:

```Rust
fn sp<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, &'a str, E> {
  let chars = " \t\r\n";

  take_while(move |c| chars.contains(c))(i)
}
```
Space should be obvious.


```Rust
fn float<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, f64, E> {
  flat_map!(i, recognize_float, parse_to!(f64))
}
```
nom provided features `recognize_float` and `parse_to` to do it automatically.

```Rust
fn parse_str<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, &'a str, E> {
    escaped!(i, call!(alphanumeric), '\\', one_of!("\"n\\"))
}
```
`call!()` is a macro to call a named function as callback. It can also take in argument like below.
```Rust
  fn take_wrapper(input: &[u8], i: u8) -> IResult<&[u8], &[u8]> { take!(input, i * 10) }

  // will make a parser taking 20 bytes
  named!(parser, call!(take_wrapper, 2));
```

For `one_of!()`:
```Rust
named!(simple<char>, one_of!(&b"abc"[..]));
assert_eq!(simple(b"a123"), Ok((&b"123"[..], 'a')));

named!(a_or_b<&str, char>, one_of!("ab汉"));
assert_eq!(a_or_b("汉jiosfe"), Ok(("jiosfe", '汉')));
```


This following code will extract string lexical elements by `{"..."}`. We can see that delimiters actually helps programmer to write parsers.
```Rust
fn string<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, &'a str, E> {
  let (i, _) = char('\"')(i)?;
  context("string", terminated(parse_str, char('\"')))(i)
}
```

Boolean is a simple tag.
```Rust
fn boolean<'a, E: ParseError<&'a str>>(input: &'a str) ->IResult<&'a str, bool, E> {
  alt( (
      |i| tag("false")(i).map(|(i,_)| (i, false)),
      |i| tag("true")(i).map(|(i,_)| (i, true))
  ))(input)

}
```


```Rust
fn array<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, Vec<JsonValue>, E> {
  let (i, _) = char('[')(i)?;

  context(
    "array",
    terminated(
      |i| separated_listc(i, preceded(sp, char(',')), value),
      preceded(sp, char(']')))
     )(i)
}
fn key_value<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, (&'a str, JsonValue), E> {
  separated_pair!(i, preceded!(sp, string), preceded!(sp, char!(':')), value)
}
```

`preceded` is a tool to check if defined something is following.

```Rust
named!(parser<&str, &str>, preceded!(char!('-'), alpha1));

assert_eq!(parser("-abc"), Ok(("", "abc")));
assert_eq!(parser("abc"), Err(Err::Error(("abc", ErrorKind::Char))));
assert_eq!(parser("-123"), Err(Err::Error(("123", ErrorKind::Alpha))));
```

`separated_list` is a string `split` in native Rust (or other languages).


```Rust
fn hash<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, HashMap<String, JsonValue>, E> {
  let (i, _) = char('{')(i)?;
  context(
    "map",
    terminated(
      |i| map!(i,
        separated_list!(preceded!(sp, char!(',')), key_value),
        |tuple_vec| tuple_vec
          .into_iter()
          .map(|(k, v)| (String::from(k), v))
          .collect()
      ),
      preceded(sp, char('}')))
     )(i)
}
```

`terminated()!` returns the result of its first parser if both succeed

```Rust
named!(parser<&str, &str>, terminated!(alpha1, char!(';')));

assert_eq!(parser("abc;"), Ok(("", "abc")));
assert_eq!(parser("abc,"), Err(Err::Error((",", ErrorKind::Char))));
assert_eq!(parser("123;"), Err(Err::Error(("123;", ErrorKind::Alpha))));
```

and it also takes the third parameter which is a callback like the second parameter in nom 4.3.
```Rust
map!(i,
    separated_list!(preceded!(sp, char!(',')), key_value),
    |tuple_vec| tuple_vec
        .into_iter()
        .map(|(k, v)| (String::from(k), v))
        .collect()
)
```

At the end:

```Rust
fn value<'a, E: ParseError<&'a str>>(i: &'a str) ->IResult<&'a str, JsonValue, E> {
  preceded!(i,
    sp,
    alt!(
      hash    => { |h| JsonValue::Object(h)            } |
      array   => { |v| JsonValue::Array(v)             } |
      string  => { |s| JsonValue::Str(String::from(s)) } |
      float   => { |f| JsonValue::Num(f)               } |
      boolean => { |b| JsonValue::Boolean(b)           }
    ))
}
```
`alt` try a list of parsers and return the result of the first successful one.


