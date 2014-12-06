# Changelog domnikl/statsd-php

## 2.0.0

* renamed Socket classes: Socket is now a UdpSocket + there is a new TcpSocket class
* batch messages are split to fit into the configured MTU
* sampling all metrics must now be configured on the client - no longer in the connection
* endTiming() returns the time measured
* for development there is a new (simple) process for running integration tests and such using make

## 1.1.0

* added support for [sets](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#sets)
* added support for [gauges](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#gauges)
* support batch-sending of metrics
* support sampling of metrics

## 1.0.2

* ignore errors when writing on the UDP sockets

## 1.0.1

* ignore all exceptions and errors which are thrown when writing to the UDP socket

## 1.0.0

* first version supporting counters, timings
