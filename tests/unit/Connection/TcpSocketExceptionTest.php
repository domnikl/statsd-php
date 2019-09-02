<?php declare(strict_types=1);

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\TcpSocketException;
use PHPUnit\Framework\TestCase;

class TcpSocketExceptionTest extends TestCase
{
    public function testCanGetMessage()
    {
        $e = new TcpSocketException('localhost', 666, 'Connection refused');
        $this->assertEquals('Couldn\'t connect to host "localhost:666": Connection refused', $e->getMessage());
    }
}
