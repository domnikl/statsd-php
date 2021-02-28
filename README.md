# statsd-php

⚠️ This repo is abandoned and will no longer be maintained. Please use [slickdeals/statsd](https://github.com/Slickdeals/statsd-php) instead.

A PHP client library for the statistics daemon ([statsd](https://github.com/etsy/statsd)) intended to send metrics from PHP applications.

[![Build Status](https://github.com/domnikl/statsd-php/workflows/Build%20statsd-php/badge.svg)](https://github.com/domnikl/statsd-php/actions)
[![Donate](https://img.shields.io/badge/donate-paypal-blue.svg?style=flat-square)](https://paypal.me/DominikLiebler)

## Installation

The best way to install statsd-php is to use Composer and add the following to your project's `composer.json` file:

```javascript
{
    "require": {
        "domnikl/statsd": "~3.0"
    }
}
```

## Usage

```php
<?php
$connection = new \Domnikl\Statsd\Connection\UdpSocket('localhost', 8125);
$statsd = new \Domnikl\Statsd\Client($connection, "test.namespace");

// the global namespace is prepended to every key (optional)
$statsd->setNamespace("test");

// simple counts
$statsd->increment("foo.bar");
$statsd->decrement("foo.bar");
$statsd->count("foo.bar", 1000);
```

When establishing the connection to statsd and sending metrics, errors will be suppressed to prevent your application from crashing.

If you run statsd in TCP mode, there is also a `\Domnikl\Statsd\Connection\TcpSocket` adapter that works like the `UdpSocket` except that it throws a `\Domnikl\Statsd\Connection\TcpSocketException` if no connection could be established.
Please consider that unlike UDP, TCP is used for reliable networks and therefor exceptions (and errors) will not be suppressed in TCP mode.

### [Timings](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#timing)

```php
<?php
// timings
$statsd->timing("foo.bar", 320);
$statsd->time("foo.bar.bla", function() {
    // code to be measured goes here ...
});

// more complex timings can be handled with startTiming() and endTiming()
$statsd->startTiming("foo.bar");
// more complex code here ...
$statsd->endTiming("foo.bar");
```

### Memory profiling

```php
<?php
// memory profiling
$statsd->startMemoryProfile('memory.foo');
// some complex code goes here ...
$statsd->endMemoryProfile('memory.foo');

// report peak usage
$statsd->memory('foo.memory_peak_usage');
```

### [Gauges](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#gauges)

statsd supports gauges, arbitrary values which can be recorded. 

This method accepts both absolute (3) and delta (+11) values. 

*NOTE:* Negative values are treated as delta values, not absolute.

```php
<?php
// Absolute value
$statsd->gauge('foobar', 3);

// Pass delta values as a string. 
// Accepts both positive (+11) and negative (-4) delta values.
$statsd->gauge('foobar', '+11'); 
```

### [Sets](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#sets)

statsd supports sets, so you can view the uniqueness of a given value.

```php
<?php
$statsd->set('userId', 1234);
```

### disabling sending of metrics

To disable sending any metrics to the statsd server, you can use the `Domnikl\Statsd\Connection\Blackhole` connection
 class instead of the default socket abstraction. This may be incredibly useful for feature flags. Another options is
to use `Domnikl\Statsd\Connection\InMemory` connection class, that will collect your messages but won't actually send them.

## Authors

Original author: Dominik Liebler <liebler.dominik@gmail.com>
Several other [contributors](https://github.com/domnikl/statsd-php/graphs/contributors) - Thank you!
