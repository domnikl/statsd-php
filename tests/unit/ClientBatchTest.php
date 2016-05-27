<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/ConnectionMock.php';

use Domnikl\Statsd\Client as Client;

class ClientBatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Domnikl\Statsd\Client
     */
    private $client;

    /**
     * @var ConnectionMock
     */
    private $connection;

    public function setUp()
    {
        $this->connection = new ConnectionMock();
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

        $this->client->setMaxBatchSize(100);
        $this->client->increment("foobar", 1);

        $message = $this->connection->getLastMessage();
        $this->assertNull($message);

        $this->client->increment(str_pad("foobar", 100, "_"), 1);
        $message = $this->connection->getLastMessage();
        $this->assertNotNull($message);
	}

	public function testEndBatch()
	{
		$this->client->startBatch();
		$this->client->count("foobar", 1);
		$this->client->count("foobar", 2);
		$this->client->endBatch();

		$this->assertFalse($this->client->isBatch());
		$this->assertSame("foobar:1|c\nfoobar:2|c", $this->connection->getLastMessage());

		// run a new batch => don't send the old values!

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

    public function testSendBatch()
    {
        $this->client->startBatch();
        $this->client->count("foobar", 5);
        $this->client->count("foobar", 6);
        $this->client->sendBatch();
        $this->assertTrue($this->client->isBatch());
        $this->assertSame("foobar:5|c\nfoobar:6|c", $this->connection->getLastMessage());
    }

    public function testGetMaxBatchSize()
    {
        $this->client->startBatch();

        $this->client->setMaxBatchSize(1024);
        $this->assertEquals($this->client->getMaxBatchSize(), 1024);

        $this->client->setMaxBatchSize(null);
        $this->assertEquals($this->client->getMaxBatchSize(), Client::DEFAULT_MAX_BATCH_SIZE);

        $this->client->setMaxBatchSize(0);
        $this->assertEquals($this->client->getMaxBatchSize(), 0);
    }
}
