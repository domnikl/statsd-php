<?php declare(strict_types=1);

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\UdpSocket;
use PHPUnit\Framework\TestCase;

class UdpSocketTest extends TestCase
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
        $this->assertEquals(ini_get('default_socket_timeout'), $connection->getTimeout());
        $this->assertFalse($connection->isPersistent());
    }
}
