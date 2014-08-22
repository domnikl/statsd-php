<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../../../../lib/Domnikl/Statsd/Client.php';
require_once __DIR__ . '/ConnectionMock.php';

use Domnikl\Statsd\Client as Client;

class ClientTest extends \PHPUnit_Framework_TestCase
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
        $this->_client = new Client($this->_connection, 'test');
    }

    public function testInit()
    {
        $client = new Client(new \Domnikl\Test\Statsd\ConnectionMock());
        $this->assertEquals('', $client->getNamespace());
    }

    public function testNamespace()
    {
        $client = new Client(new \Domnikl\Test\Statsd\ConnectionMock(), 'test.foo');
        $this->assertEquals('test.foo', $client->getNamespace());

        $client->setNamespace('bar.baz');
        $this->assertEquals('bar.baz', $client->getNamespace());
    }

    public function testCount()
    {
        $this->_client->count('foo.bar', 100);
        $this->assertEquals(
            'test.foo.bar:100|c',
            $this->_connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testCountWithSamplingRate()
    {
        $this->_connection->setForceSampling(true);
        $this->_client->count('foo.baz', 100, 1);
        $this->assertEquals(
            'test.foo.baz:100|c|@1',
            $this->_connection->getLastMessage()
        );
    }

    public function testIncrement()
    {
        $this->_client->increment('foo.baz');
        $this->assertEquals(
            'test.foo.baz:1|c',
            $this->_connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testIncrementWithSamplingRate()
    {
        $this->_connection->setForceSampling(true);
        $this->_client->increment('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:1|c|@1',
            $this->_connection->getLastMessage()
        );
    }

    public function testDecrement()
    {
        $this->_client->decrement('foo.baz');
        $this->assertEquals(
            'test.foo.baz:-1|c',
            $this->_connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testDecrementWithSamplingRate()
    {
        $this->_connection->setForceSampling(true);
        $this->_client->decrement('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:-1|c|@1',
            $this->_connection->getLastMessage()
        );
    }

    public function testTiming()
    {
        $this->_client->timing('foo.baz', 2000);
        $this->assertEquals(
            'test.foo.baz:2000|ms',
            $this->_connection->getLastMessage()
        );
    }


    /**
     * @group sampling
     */
    public function testTimingWithSamplingRate()
    {
        $this->_connection->setForceSampling(true);
        $this->_client->timing('foo.baz', 2000, 1);
        $this->assertEquals(
            'test.foo.baz:2000|ms|@1',
            $this->_connection->getLastMessage()
        );
    }

    public function testStartEndTiming()
    {
        $key = 'foo.bar';
        $this->_client->startTiming($key);
        usleep(10000);
        $this->_client->endTiming($key);
        
        // ranges between 1000 and 1001ms
        $this->assertRegExp('/^test\.foo\.bar:1[01]\|ms$/', $this->_connection->getLastMessage());
    }

    /**
     * @group sampling
     */
    public function testStartEndTimingWithSamplingRate()
    {
        $this->_connection->setForceSampling(true);
        $this->_client->startTiming('foo.baz');
        usleep(10000);
        $this->_client->endTiming('foo.baz', 1);

        // ranges between 1000 and 1001ms
        $this->assertRegExp('/^test\.foo\.baz:1[01]\|ms\|@1$/', $this->_connection->getLastMessage());
    }

    public function testTimeClosure()
    {
        $evald = $this->_client->time('foo', function() {
            return "foobar";
        });

        $this->assertEquals('foobar', $evald);
        $this->assertRegExp('/test\.foo\.baz:100[0|1]{1}|ms|@0.1/', $this->_connection->getLastMessage());
    }

    /**
     * @group memory
     */
    public function testMemory()
    {
        $this->_client->memory('foo.bar');
        $this->assertRegExp('/test\.foo\.bar:[0-9]{4,}|c/', $this->_connection->getLastMessage());
    }

    /**
     * @group memory
     */
    public function testMemoryProfile()
    {
        $this->_client->startMemoryProfile('foo.bar');
        $memoryUsage = memory_get_usage();
        $foobar = "fooooooooooooooooooooooooooooooooooooooooooooooooooooooobar";
        $this->_client->endMemoryProfile('foo.bar');

        $message = $this->_connection->getLastMessage();
        $this->assertRegExp('/test\.foo\.bar:[0-9]{4,}|c/', $message);


        preg_match('/test\.foo\.bar\:([0-9]*)|c/', $message, $matches);
        $this->assertGreaterThan(0, $matches[1]);
    }

    public function testGauge()
    {
        $this->_client->gauge("foobar", 333);
        
        $message = $this->_connection->getLastMessage();
        $this->assertEquals('test.foobar:333|g', $message);
    }

    public function testSet()
    {
        $this->_client->set("barfoo", 666);

        $message = $this->_connection->getLastMessage();
        $this->assertEquals('test.barfoo:666|s', $message);
    }

}
