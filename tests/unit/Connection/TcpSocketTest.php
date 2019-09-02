<?php declare(strict_types=1);

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\TcpSocket;
use PHPUnit\Framework\TestCase;

class TcpSocketTest extends TestCase
{
    public function testInit()
    {
        $connection = new TcpSocket('localhost', 8125, 10, true);

        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertEquals(10, $connection->getTimeout());
        $this->assertTrue($connection->isPersistent());
    }

    public function testInitDefaults()
    {
        $connection = new TcpSocket();

        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
        $this->assertEquals(ini_get('default_socket_timeout'), $connection->getTimeout());
        $this->assertFalse($connection->isPersistent());
    }

    public function testThrowsExceptionWhenTryingToConnectToNotExistingServer()
    {
        $this->expectException(\Domnikl\Statsd\Connection\TcpSocketException::class);
        $this->expectExceptionMessage('Couldn\'t connect to host "localhost:66000":');

        $connection = new TcpSocket('localhost', 66000, 1);
        $connection->send('foobar');
    }
}
