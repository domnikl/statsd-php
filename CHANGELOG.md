# Changelog domnikl/statsd-php

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
