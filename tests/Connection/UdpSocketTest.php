<?php

namespace Domnikl\Test\Statsd\Connection;

require_once __DIR__ . '/../../lib/Connection/UdpSocket.php';

class UdpSocketTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $connection = new \Domnikl\Statsd\Connection\UdpSocket('localhost', 8125, 10, true);
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertEquals(10, $connection->getTimeout());
        $this->assertTrue($connection->isPersistent());
    }

    public function testInitDefaults()
    {
        $connection = new \Domnikl\Statsd\Connection\UdpSocket();
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertNull($connection->getTimeout());
        $this->assertFalse($connection->isPersistent());
        $this->assertFalse($connection->forceSampling());
    }
}
