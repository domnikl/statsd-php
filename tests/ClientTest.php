<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../lib/Client.php';
require_once __DIR__ . '/ConnectionMock.php';

use Domnikl\Statsd\Client as Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ConnectionMock
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = new ConnectionMock();
        $this->client = new Client($this->connection, 'test');
    }

    public function testInit()
    {
        $client = new Client(new ConnectionMock());
        $this->assertEquals('', $client->getNamespace());
    }

    public function testNamespace()
    {
        $client = new Client(new ConnectionMock(), 'test.foo');
        $this->assertEquals('test.foo', $client->getNamespace());

        $client->setNamespace('bar.baz');
        $this->assertEquals('bar.baz', $client->getNamespace());
    }

    public function testCount()
    {
        $this->client->count('foo.bar', 100);
        $this->assertEquals(
            'test.foo.bar:100|c',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testCountWithSamplingRate()
    {
        $this->connection->setForceSampling(true);
        $this->client->count('foo.baz', 100, 1);
        $this->assertEquals(
            'test.foo.baz:100|c|@1',
            $this->connection->getLastMessage()
        );
    }

    public function testIncrement()
    {
        $this->client->increment('foo.baz');
        $this->assertEquals(
            'test.foo.baz:1|c',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testIncrementWithSamplingRate()
    {
        $this->connection->setForceSampling(true);
        $this->client->increment('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:1|c|@1',
            $this->connection->getLastMessage()
        );
    }

    public function testDecrement()
    {
        $this->client->decrement('foo.baz');
        $this->assertEquals(
            'test.foo.baz:-1|c',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testDecrementWithSamplingRate()
    {
        $this->connection->setForceSampling(true);
        $this->client->decrement('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:-1|c|@1',
            $this->connection->getLastMessage()
        );
    }

    public function testCanMeasureTimingWithClosure()
    {
        $this->client->timing('foo.baz', 2000);
        $this->assertEquals(
            'test.foo.baz:2000|ms',
            $this->connection->getLastMessage()
        );
    }


    /**
     * @group sampling
     */
    public function testTimingWithSamplingRate()
    {
        $this->connection->setForceSampling(true);
        $this->client->timing('foo.baz', 2000, 1);
        $this->assertEquals(
            'test.foo.baz:2000|ms|@1',
            $this->connection->getLastMessage()
        );
    }

    public function testCanMeasureTimingByStartingAndEndingTiming()
    {
        $key = 'foo.bar';
        $this->client->startTiming($key);
        usleep(10000);
        $this->client->endTiming($key);
        
        // ranges between 1000 and 1001ms
        $this->assertRegExp('/^test\.foo\.bar:1[0-9]\|ms$/', $this->connection->getLastMessage());
    }

    public function testEndTimingReturnsTiming()
    {
        $key = 'foo.bar';
        $this->assertNull($this->client->endTiming($key));

        $sleep = 10000;
        $this->client->startTiming($key);
        usleep($sleep);

        $this->assertGreaterThanOrEqual($sleep / 1000, $this->client->endTiming($key));
    }
    
    /**
     * @group sampling
     */
    public function testStartEndTimingWithSamplingRate()
    {
        $this->connection->setForceSampling(true);
        $this->client->startTiming('foo.baz');
        usleep(10000);
        $this->client->endTiming('foo.baz', 1);

        // ranges between 1000 and 1001ms
        $this->assertRegExp('/^test\.foo\.baz:1[0-9]\|ms\|@1$/', $this->connection->getLastMessage());
    }

    public function testTimeClosure()
    {
        $evald = $this->client->time('foo', function() {
            return "foobar";
        });

        $this->assertEquals('foobar', $evald);
        $this->assertRegExp('/test\.foo\.baz:100[0|1]{1}|ms|@0.1/', $this->connection->getLastMessage());
    }

    /**
     * @group memory
     */
    public function testMemory()
    {
        $this->client->memory('foo.bar');
        $this->assertRegExp('/test\.foo\.bar:[0-9]{4,}|c/', $this->connection->getLastMessage());
    }

    /**
     * @group memory
     */
    public function testMemoryProfile()
    {
        $this->client->startMemoryProfile('foo.bar');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $memoryUsage = memory_get_usage();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $foobar = "fooooooooooooooooooooooooooooooooooooooooooooooooooooooobar";
        $this->client->endMemoryProfile('foo.bar');

        $message = $this->connection->getLastMessage();
        $this->assertRegExp('/test\.foo\.bar:[0-9]{4,}|c/', $message);

        preg_match('/test\.foo\.bar\:([0-9]*)|c/', $message, $matches);
        $this->assertGreaterThan(0, $matches[1]);
    }

    public function testGauge()
    {
        $this->client->gauge("foobar", 333);

        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.foobar:333|g', $message);
    }

    public function testSet()
    {
        $this->client->set("barfoo", 666);

        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.barfoo:666|s', $message);
    }
}
