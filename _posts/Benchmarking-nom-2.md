---
title: Benchmarking nom 2
date: 2019-05-20 10:08:27
categories: Rust
tags:
- Rust
- Parser
- nom
---

## Prolog

In the previous post : "Benchmarking nom". We tried a test without really making use of the real feature that nom provide. I won't go deep this time either, but let's make `is_hex_digit` really working and to ensure that no error will be passed into conversion.

## Changes

```Rust
pub fn is_hex_digit(c: char) -> bool {
    c.is_digit(16)
}
```

The parsing function of `direct.rs` becomes:

```Rust
fn structure1(c: &str) -> Result<Color, &str> {
    macro_rules! mc1 {
        ($from:expr,$to:expr) => {{
            for ele in c[$from..$to].chars() {
                if !is_hex_digit(ele) {
                    return Err("Oh no");
                }
            }
            from_hex(&c[$from..$to]).unwrap()
        }};
    }
    Ok(Color {
        red: mc1!(1, 3),
        green: mc1!(3, 5),
        blue: mc1!(5, 7),
        number1: to_i64(&c[7..7 + 8]).unwrap(),
        number2: to_i64(&c[7 + 8..7 + 8 + 8]).unwrap(),
        number3: to_i64(&c[7 + 8 + 8..7 + 8 + 8 + 8]).unwrap(),
        number4: to_i64(&c[7 + 8 + 8 + 8..7 + 8 + 8 + 8 + 8]).unwrap(),
        number5: to_string(&c[7 + 8 * 4..7 + 8 * 4 + 100]).unwrap(),
    })
}
```

## Result

After running a `cargo +nightly bench`, we got:

```
running 1 test
test tests_direct::parse_color1 ... bench:         291 ns/iter (+/- 18)

test result: ok. 0 passed; 0 failed; 0 ignored; 1 measured; 0 filtered out

     Running target/release/deps/nomtest-a643b08a9c32cd49

running 1 test
test tests_main::parse_color1 ... bench:         725 ns/iter (+/- 163)

test result: ok. 0 passed; 0 failed; 0 ignored; 1 measured; 0 filtered out

```

I repeated a couple more times and the readings are similar.

The raw reader changes from 263 to 291 and nom changes from 670 to 725. The increment of punishment of validation is happening inside nom as well. I would suppose the power of **Zero Cost Abstraction** is happening at this corner. It is a bit out of my expection.

Parser is good for reusing many meaningful elements of the string(or bit) format. So without nesting the same element inside the context like `json`, `toml`, `xml`, parsing engine does not payout much.