<?php

require __DIR__ . '/../../vendor/autoload.php';

$connection = new \Domnikl\Statsd\Connection\TcpSocket('localhost', 8126);
$statsd = new \Domnikl\Statsd\Client($connection, "tcp.test.namespace");

while (true) {
    $statsd->startTiming('timing.while');
    $statsd->increment('customer.signed_up', 10);
    sleep(2);
    $statsd->count('products.viewed', rand(1, 100));
    $statsd->endTiming('timing.while');
}
