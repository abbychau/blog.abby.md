---
title: Diary
date: 2019-05-16 15:04:24
tags: Diary
---

# Multicasting in Rust

## What is multicasting?

Multicast is in parallel with concepts `unicast`, `broadcast` and `anycast`.

- `unicast`: single source to single target (TCP or UDP)
- `broadcast`: many sources to many targets on a **single network** (UDP)
- `anycast`: single source to **one of many targets** (TCP and UDP)
- `multicast`: many source to many target (UDP and RTP)

There are many setting details for the first three group communications but now we focus on the last one.

## Features of `multicast`

- Multicasting gives the ability for many sources to deliver packets to many destinations. 
- Similar to broadcasting, but `multicast` allows for these distributed packets to be delivered to more nodes than just the ones attached to the hosts network. 
- Multicast attempts to reduce congestion by requiring services that wish to receive multicast packets to “join” a multicast address for interest.
- `joins` are then announced to upstream routers, where different network address spaces define the scope or range up the network stack that these memberships should be announced (see rfc5771 and rfc7346 for IPv4 and IPv6 registrations). 
- This is to help prevent floods of multicast traffic hitting the Internet.


## When should you use multicasting?
Whenever you need to deliver the same data to many destinations. Multicast addresses usually look like an obvious range like `224.0.0.251` (v4) or `FACE::FB` (v6) .

## Multicasting in Rust
- There is a sender and a receiver (like UDP), but the desitination IP address being sent to is a [multicast address](https://en.wikipedia.org/wiki/Multicast_address).

### Steps

1. If you want to both send and receive multicast packets, you will need to create two sockets
    - one for outbound multicast packets, and one for inbound
    
2. Include `socket2`
    - `socket2 = { version = "0.3.4", features = ["reuseport"] }`
    
3. Add `extern crate socket2;` to your `lib.rs` or `main.rs` 

```Rust
#[macro_use]
extern crate lazy_static;
extern crate socket2;

use std::net::{IpAddr, Ipv4Addr, Ipv6Addr, SocketAddr};

pub const PORT: u16 = 7645;

lazy_static! {
    pub static ref IPV4: IpAddr = Ipv4Addr::new(224, 0, 0, 123).into();
    pub static ref IPV6: IpAddr = Ipv6Addr::new(0xFF02, 0, 0, 0, 0, 0, 0, 0x0123).into();
}
```

4. Test address to see if they are in multicast

```Rust
#[test]
fn test_ipv4_multicast() {
    assert!(IPV4.is_multicast());
}

#[test]
fn test_ipv6_multicast() {
    assert!(IPV6.is_multicast());
}
```

Then we make a test client:

```Rust
use std::sync::{Arc, Barrier};
use std::sync::atomic::{AtomicBool, Ordering};
use std::thread::{self, JoinHandle};

fn multicast_listener(
    response: &'static str,
    client_done: Arc<AtomicBool>,
    addr: SocketAddr,
) -> JoinHandle<()> {
    // A barrier to not start the client test code until after the server is running
    let server_barrier = Arc::new(Barrier::new(2));
    let client_barrier = Arc::clone(&server_barrier);

    let join_handle = std::thread::Builder::new()
        .name(format!("{}:server", response))
        .spawn(move || {
            // socket creation will go here...

            server_barrier.wait();
            println!("{}:server: is ready", response);

            // We'll be looping until the client indicates it is done.
            while !client_done.load(std::sync::atomic::Ordering::Relaxed) {
                // test receive and response code will go here...
            }

            println!("{}:server: client is done", response);
        })
        .unwrap();

    client_barrier.wait();
    join_handle
}

/// This will guarantee we always tell the server to stop
struct NotifyServer(Arc<AtomicBool>);
impl Drop for NotifyServer {
    fn drop(&mut self) {
        self.0.store(true, Ordering::Relaxed);
    }
}

/// Our generic test over different IPs
fn test_multicast(test: &'static str, addr: IpAddr) {
    assert!(addr.is_multicast());
    let addr = SocketAddr::new(addr, PORT);

    let client_done = Arc::new(AtomicBool::new(false));
    NotifyServer(Arc::clone(&client_done));

    multicast_listener(test, client_done, addr);

    // client test code send and receive code after here
    println!("{}:client: running", test);
}

#[test]
fn test_ipv4_multicast() {
    test_multicast("ipv4", *IPV4);
}

#[test]
fn test_ipv6_multicast() {
    test_multicast("ipv6", *IPV6);
}
```

5. Real listener
```Rust
use std::io;
use std::time::Duration;

use socket2::{Domain, Protocol, SockAddr, Socket, Type};

// this will be common for all our sockets
fn new_socket(addr: &SocketAddr) -> io::Result<Socket> {
    let domain = if addr.is_ipv4() {
        Domain::ipv4()
    } else {
        Domain::ipv6()
    };

    let socket = Socket::new(domain, Type::dgram(), Some(Protocol::udp()))?;

    // we're going to use read timeouts so that we don't hang waiting for packets
    socket.set_read_timeout(Some(Duration::from_millis(100)))?;

    Ok(socket)
}

fn join_multicast(addr: SocketAddr) -> io::Result<Socket> {
    let ip_addr = addr.ip();

    let socket = new_socket(&addr)?;

    // depending on the IP protocol we have slightly different work
    match ip_addr {
        IpAddr::V4(ref mdns_v4) => {
            // join to the multicast address, with all interfaces
            socket.join_multicast_v4(mdns_v4, &Ipv4Addr::new(0, 0, 0, 0))?;
        }
        IpAddr::V6(ref mdns_v6) => {
            // join to the multicast address, with all interfaces (ipv6 uses indexes not addresses)
            socket.join_multicast_v6(mdns_v6, 0)?;
            socket.set_only_v6(true)?;
        }
    };

    // bind us to the socket address.
    socket.bind(&SockAddr::from(addr))?;
    Ok(socket)
}
```