<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../lib/Client.php';
require_once __DIR__ . '/ConnectionMock.php';

use Domnikl\Statsd\Client as Client;

class ClientBatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Domnikl\Statsd\Client
     */
    private $client;

    /**
     * @var \Domnikl\Test\Statsd\ConnectionMock
     */
    private $connection;

    public function setUp()
    {
        $this->connection = new \Domnikl\Test\Statsd\ConnectionMock();
        $this->client = new Client($this->connection);
    }

	public function testInit()
	{
		$this->assertFalse($this->client->isBatch());
	}

	public function testStartBatch()
	{
		$this->client->startBatch();
		$this->assertTrue($this->client->isBatch());
	}

	public function testSendIsRecordingInBatch()
	{
		$this->client->startBatch();
		$this->client->increment("foobar", 1);

		$message = $this->connection->getLastMessage();
		$this->assertNull($message);
	}

	public function testEndBatch()
	{
		$this->client->startBatch();
		$this->client->count("foobar", 1);
		$this->client->count("foobar", 2);
		$this->client->endBatch();

		$this->assertFalse($this->client->isBatch());
		$this->assertSame("foobar:1|c\nfoobar:2|c", $this->connection->getLastMessage());

		// run a new batch => don't send old values!

		$this->client->startBatch();
		$this->client->count("baz", 100);
		$this->client->count("baz", 300);
		$this->client->endBatch();

		$this->assertFalse($this->client->isBatch());
		$this->assertSame("baz:100|c\nbaz:300|c", $this->connection->getLastMessage());
	}

	public function testCancelBatch()
	{
		$this->client->startBatch();
		$this->client->count("foobar", 4);
		$this->client->cancelBatch();

		$this->assertFalse($this->client->isBatch());
		$this->assertNull($this->connection->getLastMessage());
	}
}
