<?php

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\UdpSocket;

class UdpSocketTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $connection = new UdpSocket('localhost', 8125, 10, true);
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertEquals(10, $connection->getTimeout());
        $this->assertTrue($connection->isPersistent());
    }

    public function testInitDefaults()
    {
        $connection = new UdpSocket();
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertNull($connection->getTimeout());
        $this->assertFalse($connection->isPersistent());
        $this->assertFalse($connection->forceSampling());
    }
}
