---
title: Development Story on a Standalone Eventually Consistent KV-Store
date: 2019-10-16 09:10:49
categories:
- Rust
- Key-Value Store
tags: 
- key-value
- Database
- Concurrent
- Rust
- Development
---

**Hint: If you are looking for Key-value store for your arbitrary values. This won't suit your case.**

# Story
I am in development of my own social networking site. After some careful consideration, I decided to use MySQL, but in a NoSQL way, like MongoDB.

The reason is straight-forward as I want scalibility. 

Of course mysql can do very good scale by setting up MGM, SQL nodes, and data nodes. However, there are some problems:

1. The overhead of cluster is very high when the scale is small that we need 5 servers to serve the capacity of the data volume less than 2 servers.

2. Network is busy for synchronizing and query. And the speed is limited by the synchronizing speed.

3. MySQL cluster is based on memory which with is with Maximum capacity. 

There are many more points for MySQL cluster is not suitable for most of the business cases, so please search around if you are interested. This is not the core of this article.

Then, **why not MongoDB?**, because making structure can make better use of disk space and this ease the backend development too. I don't think that NoSQL is only for unstructured data. Besides, MySQL provides JSON type as well.

# So we have a relation problem
In SaaS (or whatever aaS you named) like AWS DynamoDB. AWS charges you 100bytes per item of storage to account for indexes, which is very inefficient, and thus expensive (even though it is linear). Not mentioning you need usually two or more for each function.

The functionality for MongoDB is limited as well. ([link](https://www.tutorialspoint.com/mongodb/mongodb_indexing_limitations.htm))

And, MySQL will force you to do partitioning in order to continue scaling, which is not easy for business logics.

# Core of the problem
The core of the problem is indexing.
Or in another word, index is prohibiting the growth of a single server because the memory of a single server is limited.

# Let's try to make index efficient
For all of the business logic in the real world, the number is not exceeding unsigned 32bit. And even for some extreme case like "View count of Gangnam Style", the count can be handled by 64bit.

unsigned 64 bit integer is even large enough for relationship counts.

If we assign 32GB of ram of the computer. 32GB/64bit= `INT_MAX of 32bit integer`. I would think that this is enough for storing relations for most global scale applications.

So in my mind, the architecture of database is just as simple as "NoSQL Store" + "Index Store". We seperate the main problem, indexing, to another server and make the store efficient.

# Nature of Index
The structure of index is very simple and fixed; which is `u64 -> [u64;n]`.
So we usually invest too much overhead to handle arbitrary datatypes, serializations, data formating, byte-network-transport interfacings, etc.

My idea is to make a server taking in an integer and return a list of integer immediately.

# Journey of constructing a data-structure
Data structure is the bread and butter of a store. 

Let's start with Hashmap, which is a very simple in-memory internal store. 

Take `Hashmap<u64, Vec<u64>>` as an example.

...to be continued...

# Hash Functions

https://github.com/JesperAxelsson/rust-intmap
