<?php

require __DIR__ . '/../../vendor/autoload.php';

$connection = new \Domnikl\Statsd\Connection\UdpSocket('127.0.0.1', 8125);
$statsd = new \Domnikl\Statsd\Client($connection, "udp.test.namespace", 0.1);

while (true) {
    $statsd->startTiming('timing.while');
    sleep(2);
    $statsd->count('products.viewed', rand(1, 100));
    $statsd->endTiming('timing.while');

    $statsd->startBatch();
    for ($i = 0; $i < 1000; $i++) {
        $statsd->increment('batch');
    }
    $statsd->increment('batch.end');
    $statsd->endBatch();
}
