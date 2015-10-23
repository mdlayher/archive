replicache
==========

Distributed memory cache system, written in Go. MIT Licensed.

replicache is intended to be used as a tool for learning about distributed systems development, and will likely
never become as full-featured as other solutions such as [memcached](https://github.com/memcached/memcached) or
[redis](https://github.com/antirez/redis).  Feel free to use it if it suits your needs, but it is not recommended
to use it in production systems.

Full protocol documentation for replicache may be found in [PROTOCOL.md](https://github.com/mdlayher/replicache/blob/master/PROTOCOL.md).

Installation
============

To download, build, and install replicache, simply run:

`go get github.com/mdlayher/replicache`

replicache may be started on a custom port through use of the `-port` flag:

`$ replicache -port=8080`

Client
======

A Go replicache client is packaged with replicache, and is used within the server itself for communication with
other instances.

Full documentation for this client can be found on [GoDoc](http://godoc.org/github.com/mdlayher/replicache/client).

Example client library usage can be found on the client's [README.md](https://github.com/mdlayher/replicache/blob/master/client/README.md).
