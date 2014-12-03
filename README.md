# statsd-php

A PHP client library for the statistics daemon ([statsd](https://github.com/etsy/statsd)) intended to send metrics from PHP applications.

[![Build Status](https://secure.travis-ci.org/domnikl/statsd-php.png?branch=master)](http://travis-ci.org/domnikl/statsd-php)

## Installation

The best way to install statsd-php is to use Composer and add the following to your project's `composer.json` file:

```javascript
{
    "require": {
        "domnikl/statsd": "~1.1"
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

If you run statsd in TCP mode, there is also a `\Domnikl\Statsd\Connection\TcpSocket` adapter that works like the `UdpSocket`.
Please consider that unlike UDP, TCP is used for reliable networks and therefor errors will not be suppressed in TCP mode.

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

```php
<?php
$statsd->gauge('foobar', 3);
```

### [Sets](https://github.com/etsy/statsd/blob/master/docs/metric_types.md#sets)

statsd supports sets, so you can view the uniqueness of a given value.

```php
<?php
$statsd->set('userId', 1234);
```

### disabling sending of metrics

To disable sending any metrics to the statsd server, you can use the `Domnikl\Statsd\Connection\Blackhole` connection class instead of the default socket abstraction.
This may be incredibly useful for feature flags.

## Authors

Original author: Dominik Liebler <liebler.dominik@gmail.com>
Several other [contributors](https://github.com/domnikl/statsd-php/graphs/contributors) - Thank you!

## License

(The MIT License)

Copyright (c) 2014 Dominik Liebler

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
