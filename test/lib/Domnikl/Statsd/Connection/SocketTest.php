<?php

namespace Domnikl\Test\Statsd\Connection;

require_once __DIR__ . '/../../../../../lib/Domnikl/Statsd/Connection/Socket.php';

class SocketTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $connection = new \Domnikl\Statsd\Connection\Socket('localhost', 8125);
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
    }

    public function testInitDefaults()
    {
        $connection = new \Domnikl\Statsd\Connection\Socket();
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertFalse($connection->forceSampling());
    }
}
