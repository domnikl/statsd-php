<?php

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\InMemory;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectSingleMessage()
    {
        $connection = new InMemory();
        $connection->send('some message');
        $this->assertCount(1, $connection->getMessages());
    }

    public function testCollectMultipleMessages()
    {
        $connection = new InMemory();
        $connection->sendMessages(['some message', 'even more messages']);
        $this->assertCount(2, $connection->getMessages());
    }

    public function testCollectDifferentMessages()
    {
        $connection = new InMemory();
        $connection->send('first message');
        $connection->sendMessages(['some more message', 'even more messages']);
        $this->assertCount(3, $connection->getMessages());
    }

    public function testClearMessages()
    {
        $connection = new InMemory();
        $connection->send('first message');
        $connection->clear();
        $this->assertCount(0, $connection->getMessages());
    }
}
