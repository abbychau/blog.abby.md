---
title: Is Golang's error handling good or bad, comparing to `try...catch`?
date: 2022-03-14 17:41:00
categories: golang
---
How would you argue that GO's `if err != nil` error handling is better or worse than `try...catch...` ?

I would like to say it is worse.

I think it's necessary to calmly talk about how PL people think about language design. 

This is very reasonable in the environment where half of them know Type Theory and half of them only have engineering backgrounds. Someone expressed that the design of golang's err is not a "mistake", but a pure taste problem. I can't agree. 

But at the same time we have to admit that even in 2022, it is a very difficult task to make the argument type essential and critical to a language without resorting to taste. I think that people expressed a very common criticism of the role of type theory in programming languages: 

One can argue that type safety provides safety, but one can also argue that **choosing safety over something else** is taste itself. 

Harper believes that languages ​​with unsafe types do not make sense, and Bryant, one of the authors of CSAPP, said that things like Standard ML simply cannot be used to write "Fast code".

People who may do programming language theory naturally feel that if the behavior of the program is unspecified or non-deterministic, it is already ridiculous/unacceptable. At the same time, they are very concerned about the expressiveness and abstraction ability of the language. 
But system guys may think that as long as it is convenient to build the system, it is good, and they don't very care about other properties. People like the latter probably don't understand (and don't care about) type theory, but it would seem arrogant to deny them a part in language design discussions (and call them cranks).


For this, my point of view is this, in 2022, is: Designing a language often has a core goal, which can be under the PL framework (for example, I want the language to be formally verified, or I want the language to be lazy), and also Probably not under the PL framework (e.g. Go's design goals are largely "easy to use" and extremely efficient concurrency). 

For the latter, it is likely that the final language will sacrifice safety (think rust's unsafe). But we also have to admit that after meeting the core goals, a large part of the design of the language is still floating. For such parts, type theory can guide how to enhance abstraction, how to make programmers make fewer mistakes, and so on. Go is typically fuxxing up on the latter. I'm not going to criticize why Go has a null pointer exception (and the language is unsafe), because it's largely the result of Go compromising on its core goals. But the design of err!=nil simply shouldn't be. The conclusion is that if you do language design without knowing anything about type theory, you may get a language that meets your core needs, but your language is likely to be very difficult to use and full of pits. People created such a product are probably not wrong to be called cranks.


The problem with a design like Go's is that it makes it harder for programmers to analyze the correctness of a program. There are actually four possible return values of Go functions, 
namely 
(no result, err not nil), 
(with result, err nil), 
(no result, err nil), 
(with result, err not nil). 

In principle, to ensure that the results of program operation are always in line with expectations, all four conditions must be checked, otherwise the program will enter an undefined state when an unchecked condition occurs. 

In fact, Go programmers often ignore the latter two, because there is an unwritten convention that the latter two situations generally do not occur. This is very bad, the correctness of the program depends on a convention that is not part of the language. 

To make matters worse, some library functions actually do not follow this convention! Because this design introduces implicit assumptions to the programmer that cannot be checked by the compiler, it leads to bugs and is therefore a bad design.


In conslusion, 
In 2022, we still use product type to indicate something that should be sum type, and still think this is a language advantage; is not acceptable. 
