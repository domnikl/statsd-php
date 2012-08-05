<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../../../../lib/Domnikl/Statsd/Client.php';
require_once __DIR__ . '/ConnectionMock.php';

use Domnikl\Statsd\Client as Client;

class ClientBatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Domnikl\Statsd\Client
     */
    protected $_client;

    /**
     * @var \Domnikl\Test\Statsd\ConnectionMock
     */
    protected $_connection;


    public function setUp()
    {
        $this->_connection = new \Domnikl\Test\Statsd\ConnectionMock();
        $this->_client = new Client($this->_connection);
    }

	public function testInit()
	{
		$this->assertFalse($this->_client->isBatch());
	}
	
	public function testStartBatch()
	{
		$this->_client->startBatch();
		$this->assertTrue($this->_client->isBatch());
	}
	
	public function testSendIsRecordingInBatch()
	{
		$this->_client->startBatch();
		$this->_client->increment("foobar", 1);
		
		$message = $this->_connection->getLastMessage();
		$this->assertNull($message);
	}
	
	public function testEndBatch()
	{
		$this->_client->startBatch();
		$this->_client->count("foobar", 1);
		$this->_client->count("foobar", 2);
		$this->_client->endBatch();
		
		$this->assertFalse($this->_client->isBatch());
		$this->assertSame("foobar:1|c\nfoobar:2|c", $this->_connection->getLastMessage());
		
		// run a new batch => don't send old values!
		
		$this->_client->startBatch();
		$this->_client->count("baz", 100);
		$this->_client->count("baz", 300);
		$this->_client->endBatch();
		
		$this->assertFalse($this->_client->isBatch());
		$this->assertSame("baz:100|c\nbaz:300|c", $this->_connection->getLastMessage());
	}
	
	public function testCancelBatch()
	{
		$this->_client->startBatch();
		$this->_client->count("foobar", 4);
		$this->_client->cancelBatch();
		
		$this->assertFalse($this->_client->isBatch());
		$this->assertNull($this->_connection->getLastMessage());
	}
}