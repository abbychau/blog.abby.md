---
title: Multicasting in Rust
date: 2019-05-16 15:04:24
categories:
- Networking
- Rust
tags: 
- Rust
- ipv4
- ipv6
- multicast
- udp
---

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


|  D类地址            |  用途                            | 
|-----|-----|
| 224.0.0.1                    |  在一个子网上的所有主机         |
| 224.0.0.2                    |  在一个子网上的所有路由器         |
| 224.0.0.4                    |  所有DVMRP协议的路由器             |
| 224.0.0.5                    |  所有开放最短路径优先（OSPF）路由器   |         
| 224.0.0.6                    |  所有OSPF指定路由器               |
| 224.0.0.9                    |  所有RIPv2路由器                  |
| 224.0.0.13                   |  所有PIM协议路由器                 |
| 224.0.0.0-224.0.0.255        |  保留作本地使用，做管理和维护任务     |     
| 239.0.0.0-239.255.255.255    |  留用做管理使用                    |


## Coding Example

This example is using the `std::net::UdpSocket`

#### Server

```Rust
use std::net::UdpSocket;
use std::net::Ipv4Addr;

fn main() {
    let mut socket = UdpSocket::bind("0.0.0.0:8888").unwrap();
    let mut buf = [0u8; 65535];
    let multi_addr = Ipv4Addr::new(234, 2, 2, 2);
    let inter = Ipv4Addr::new(0,0,0,0);
    socket.join_multicast_v4(&multi_addr,&inter);

    loop {
        let (amt, src) = socket.recv_from(&mut buf).unwrap();
        println!("received {} bytes from {:?}", amt, src);
    }
}
```

#### Client

```Rust
use std::net::UdpSocket;
use std::thread;

fn main() {
    let socket = UdpSocket::bind("0.0.0.0:9999").unwrap();
    let buf = [1u8; 15000];
    let mut count = 1473;
    socket.send_to(&buf[0..count], "234.2.2.2:8888").unwrap();

    thread::sleep_ms(1000);
}
```



## Example 2

`std::net::UdpSocket` is actually not providing all options from `libc`. `socket2` provide them.

Let's have a look on [this example](https://github.com/bluejekyll/multicast-example/blob/master/src/lib.rs).

We will use these : `use socket2::{Domain, Protocol, SockAddr, Socket, Type};`.

#### Step 1: Bind

```Rust
#[cfg(unix)]
fn bind_multicast(socket: &Socket, addr: &SocketAddr) -> io::Result<()> {
    socket.bind(&socket2::SockAddr::from(*addr))
}
```

The binding method is different from Windows and *nix, that, in Windows, 

https://docs.microsoft.com/zh-tw/windows/desktop/api/winsock/nf-winsock-bind mentions:

> For multicast operations, the preferred method is to call the bind function to associate a socket with a local IP address and then join the multicast group. Although this order of operations is not mandatory, it is strongly recommended. So a multicast application would first select an IPv4 or IPv6 address on the local computer, the wildcard IPv4 address (INADDR_ANY), or the wildcard IPv6 address (in6addr_any). The the multicast application would then call the bind function with this address in the in the sa_data member of the name parameter to associate the local IP address with the socket. If a wildcard address was specified, then Windows will select the local IP address to use. After the bind function completes, an application would then join the multicast group of interest. For more information on how to join a multicast group, see the section on Multicast Programming. This socket can then be used to receive multicast packets from the multicast group using the recv, recvfrom, WSARecv, WSARecvEx, WSARecvFrom, or WSARecvMsg functions.

In short, we need a `INADDR_ANY`.

```Rust
#[cfg(windows)]
fn bind_multicast(socket: &Socket, addr: &SocketAddr) -> io::Result<()> {
    let addr = match *addr {
        SocketAddr::V4(addr) => SocketAddr::new(Ipv4Addr::new(0, 0, 0, 0).into(), addr.port()),
        SocketAddr::V6(addr) => {
            SocketAddr::new(Ipv6Addr::new(0, 0, 0, 0, 0, 0, 0, 0).into(), addr.port())
        }
    };
    socket.bind(&socket2::SockAddr::from(addr))
}
```
#### Step 2: Join

```Rust
fn join_multicast(addr: SocketAddr) -> io::Result<UdpSocket> {
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
    bind_multicast(&socket, &addr)?;

    // convert to standard sockets
    Ok(socket.into_udp_socket())
}
```

#### Step 3: Listener and Sender

``` Rust
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
            let listener = join_multicast(addr).expect("failed to create listener");
            println!("{}:server: joined: {}", response, addr);

            server_barrier.wait();
            println!("{}:server: is ready", response);

            // We'll be looping until the client indicates it is done.
            while !client_done.load(std::sync::atomic::Ordering::Relaxed) {
                // test receive and response code will go here...
                let mut buf = [0u8; 64]; // receive buffer

                // we're assuming failures were timeouts, the client_done loop will stop us
                match listener.recv_from(&mut buf) {
                    Ok((len, remote_addr)) => {
                        let data = &buf[..len];

                        println!(
                            "{}:server: got data: {} from: {}",
                            response,
                            String::from_utf8_lossy(data),
                            remote_addr
                        );

                        // create a socket to send the response
                        let responder = new_socket(&remote_addr)
                            .expect("failed to create responder")
                            .into_udp_socket();

                        // we send the response that was set at the method beginning
                        responder
                            .send_to(response.as_bytes(), &remote_addr)
                            .expect("failed to respond");

                        println!("{}:server: sent response to: {}", response, remote_addr);
                    }
                    Err(err) => {
                        println!("{}:server: got an error: {}", response, err);
                    }
                }
            }

            println!("{}:server: client is done", response);
        })
        .unwrap();

    client_barrier.wait();
    join_handle
}
```

```Rust
fn new_sender(addr: &SocketAddr) -> io::Result<UdpSocket> {
    let socket = new_socket(addr)?;

    if addr.is_ipv4() {
        socket.set_multicast_if_v4(&Ipv4Addr::new(0, 0, 0, 0))?;

        socket.bind(&SockAddr::from(SocketAddr::new(
            Ipv4Addr::new(0, 0, 0, 0).into(),
            0,
        )))?;
    } else {
        // *WARNING* THIS IS SPECIFIC TO THE AUTHORS COMPUTER
        //   find the index of your IPv6 interface you'd like to test with.
        socket.set_multicast_if_v6(5)?;

        socket.bind(&SockAddr::from(SocketAddr::new(
            Ipv6Addr::new(0, 0, 0, 0, 0, 0, 0, 0).into(),
            0,
        )))?;
    }

    // convert to standard sockets...
    Ok(socket.into_udp_socket())
}
```


#### Step 4: Using Listener and Sender

```Rust
fn test_multicast(test: &'static str, addr: IpAddr) {
    assert!(addr.is_multicast());
    let addr = SocketAddr::new(addr, PORT);

    let client_done = Arc::new(AtomicBool::new(false));
    let notify = NotifyServer(Arc::clone(&client_done));

    multicast_listener(test, client_done, addr);

    // client test code send and receive code after here
    println!("{}:client: running", test);

    let message = b"Hello from client!";

    // create the sending socket
    let socket = new_sender(&addr).expect("could not create sender!");
    socket.send_to(message, &addr).expect("could not send_to!");

    let mut buf = [0u8; 64]; // receive buffer

    match socket.recv_from(&mut buf) {
        Ok((len, remote_addr)) => {
            let data = &buf[..len];
            let response = String::from_utf8_lossy(data);

            println!("{}:client: got data: {}", test, response);

            // verify it's what we expected
            assert_eq!(test, response);
        }
        Err(err) => {
            println!("{}:client: had a problem: {}", test, err);
            assert!(false);
        }
    }

    // make sure we don't notify the server until the end of the client test
    drop(notify);
}
```

## Conclusion

Comparing to C++ clients and servers. std libraries in Rust is much simpler, but to write codes with greater control, Rust is quite verbose but still very readable in comparison.