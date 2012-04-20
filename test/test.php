<?php

require dirname(__FILE__) . '/../lib/Statsd.php';

$statsd = new Statsd('localhost', 8125);

$statsd->count("foo.bar.bla.1", 10000, 10);
$statsd->increment("foo.bar.bla.2", 10);
$statsd->decrement("foo.bar.bla.3", 10);

$statsd->timing("foo.bar.bla.4", 210, 10);
$statsd->time("foo.bar.bla", function() {
    sleep(2.2);
}, 10);

$statsd->startTiming("foo.bar.bla.4");
sleep(1);
$statsd->endTiming("foo.bar.bla.4");