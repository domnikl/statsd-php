<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../../../../lib/Domnikl/Statsd/Connection.php';

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $connection = new \Domnikl\Statsd\Connection('localhost', 8125);
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
    }

    public function testInitDefaults()
    {
        $connection = new \Domnikl\Statsd\Connection();
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(8125, $connection->getPort());
    }
}
