<?php

require __DIR__ . '/../../vendor/autoload.php';

$connection = new \Domnikl\Statsd\Connection\UdpSocket('localhost', 8125);
$statsd = new \Domnikl\Statsd\Client($connection, "udp.test.namespace");

while (true) {
    $statsd->startTiming('timing.while');
    $statsd->increment('customer.signed_up', 10);
    sleep(2);
    $statsd->count('products.viewed', rand(1, 100));
    $statsd->endTiming('timing.while');
}
