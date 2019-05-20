---
title: Benchmarking nom
date: 2019-05-20 08:50:01
tags:
- Rust
- Parser
- nom
---

## Start

Let's take the example of hello world of `nom`:

```Rust
  assert_eq!(hex_color("#2F14DF"), Ok(("", Color {
    red: 47,
    green: 20,
    blue: 223,
  })));
```

RGB Hex code is very similar to low level TCP/UDP messages. That, for instance of `#AABBCC`, `#` means "This is a color, the following message length is 6: 0-1 is for R, 2-3:G, 4-5:B`.

That one can make a theoretically O(n) memory model for it by reading bytes by bytes.

We can also expect a worse performance from a parser doing `let (input, (red, green, blue)) = tuple((hex_primary, hex_primary, hex_primary))(input)?;`


## Common Functions

To be fair, here are the common functions shared by two parsers.
```Rust
// tools.rs
pub fn from_hex(input: &str) -> Result<u8, std::num::ParseIntError> {
    u8::from_str_radix(input, 16)
}
pub fn to_i64(input: &str) -> Result<i64, std::num::ParseIntError> {
    input.parse::<i64>()
}
pub fn is_hex_digit(_c: char) -> bool {
    true
}
pub fn is_num(c: char) -> bool {
    c.is_digit(10)
}
pub fn to_string(c: &str) -> Result<String, ()> {
    Ok(c.to_string().trim().to_string())
}
```

`is_hex_digit` is for validation of the input byte by `take_while_m_n`, which is a very common operation by parsers, but we keep it always returning true so the have the baseline first, before we want the power of the lexical engine.


# Nom Parser

We got `main.rs`, which is using `nom` as below.

```Rust
#![feature(test)]

#[macro_use]
extern crate nom;

mod tools;
use tools::*;

#[derive(Debug, PartialEq)]
pub struct Color {
    pub red: u8,
    pub green: u8,
    pub blue: u8,
    pub number1: i64,
    pub number2: i64,
    pub number3: i64,
    pub number4: i64,
    pub number5: String,
}

named!(hex_primary<&str, u8>,
  map_res!(take_while_m_n!(2, 2, is_hex_digit), from_hex)
);
named!(four_length_int<&str, i64>,
  map_res!(take!(8), to_i64)
);
named!(length8_string<&str, String>,
  map_res!(take!(8), to_string)
);
named!(length100_string<&str, String>,
  map_res!(take!(100), to_string)
);

fn from_u8(c: &str) -> Result<String, ()> {
    print!("{:?}", c);
    Ok(c.to_string())
}
named!(structure1<&str, Color>,
  do_parse!(
           tag!("#")   >>
    red:   hex_primary >>
    green: hex_primary >>
    blue:  hex_primary >>
    number1: four_length_int >>
    number2: four_length_int >>
    number3: four_length_int >>
    number4: four_length_int >>
    number5: length8_string >>
    (
      Color {
        red,
        green,
        blue,
        number1,
        number2,
        number3,
        number4,
        number5
      }
    )
  )
);

fn main() {

}

#[cfg(test)]
mod tests_main {

    extern crate test;
    use super::*;
    use test::Bencher;

    #[bench]
    fn parse_color1(b: &mut Bencher) {
        b.iter(|| structure1("#2F14DF888888887777777766666666555555551234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890"));
    }

}
```

# &str Slice Reader

And `direct.rs`, which is a direct slice access to `&str`.

```Rust
#![feature(test)]

mod tools;
use tools::*;

#[derive(Debug, PartialEq)]
pub struct Color {
    pub red: u8,
    pub green: u8,
    pub blue: u8,
    pub number1: i64,
    pub number2: i64,
    pub number3: i64,
    pub number4: i64,
    pub number5: String,
}

fn main() {}
fn structure1(c: &str) -> Result<Color, String> {
    Ok(Color {
        red: from_hex(&c[1..3]).unwrap(),
        green: from_hex(&c[3..5]).unwrap(),
        blue: from_hex(&c[5..7]).unwrap(),
        number1: to_i64(&c[7..7 + 8]).unwrap(),
        number2: to_i64(&c[7 + 8..7 + 8 + 8]).unwrap(),
        number3: to_i64(&c[7 + 8 + 8..7 + 8 + 8 + 8]).unwrap(),
        number4: to_i64(&c[7 + 8 + 8 + 8..7 + 8 + 8 + 8 + 8]).unwrap(),
        number5: to_string(&c[7 + 8 * 4..7 + 8 * 4 + 100]).unwrap(),
    })
}

#[cfg(test)]
mod tests_direct {

    extern crate test;
    use super::*;
    use test::Bencher;

    #[bench]
    fn parse_color1(b: &mut Bencher) {
        b.iter(|| structure1("#2F14DF888888887777777766666666555555551234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890"));
    }

}
```

Both of the are zero-copy during parsing.


## Result

After running a `cargo +nightly bench`, we got:

```console
running 1 test
test tests_direct::parse_color1 ... bench:         263 ns/iter (+/- 22)

test result: ok. 0 passed; 0 failed; 0 ignored; 1 measured; 0 filtered out

     Running target/release/deps/nomtest-cdc895365399f2bf

running 1 test
test tests_main::parse_color1 ... bench:         670 ns/iter (+/- 76)

test result: ok. 0 passed; 0 failed; 0 ignored; 1 measured; 0 filtered out
```

This is quite close to the expection that how `nom` is punishing on its lexical techniques. (Testing on a Apple MacBook Pro 2018 i7 16GB RAM)