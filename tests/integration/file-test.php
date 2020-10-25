<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$connection = new \Domnikl\Statsd\Connection\File('stats.log');
$statsd = new \Domnikl\Statsd\Client($connection, "file.test.namespace");

while (true) {
    $statsd->startTiming('timing.while');
    $statsd->increment('customer.signed_up', 10);
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
