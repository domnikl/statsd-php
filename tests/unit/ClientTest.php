<?php

namespace Domnikl\Test\Statsd;

use Domnikl\Statsd\Client as Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
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
        $client = new Client($this->connection, 'test', 1 / 5);
        $client->count('foo.baz', 100, 1);
        $this->assertEquals(
            'test.foo.baz:100|c|@0.2',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testCountWithSamplingRateAndTags()
    {
        $client = new Client($this->connection, 'test', 1 / 5);
        $client->count('foo.baz', 100, array('tag' => 'value'), 1);
        $this->assertEquals(
            'test.foo.baz:100|c|@0.2|#tag:value',
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
        $client = new Client($this->connection, 'test', 0.3);
        $client->increment('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:1|c|@0.3',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testIncrementWithSamplingRateAndTags()
    {
        $client = new Client($this->connection, 'test', 0.3);
        $client->increment('foo.baz', array('tag' => 'value'), 1);
        $this->assertEquals(
            'test.foo.baz:1|c|@0.3|#tag:value',
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
        $client = new Client($this->connection, 'test', 0.2);
        $client->decrement('foo.baz', 1);
        $this->assertEquals(
            'test.foo.baz:-1|c|@0.2',
            $this->connection->getLastMessage()
        );
    }

    /**
     * @group sampling
     */
    public function testDecrementWithSamplingRateAndTags()
    {
        $client = new Client($this->connection, 'test', 0.2);
        $client->decrement('foo.baz', array('tag' => 'value'), 1);
        $this->assertEquals(
            'test.foo.baz:-1|c|@0.2|#tag:value',
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
        $client = new Client($this->connection, 'test', 0.1);
        $client->timing('foo.baz', 2000, 1);
        $this->assertEquals(
            'test.foo.baz:2000|ms|@0.1',
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
        $this->assertRegExp('/^test\.foo\.bar:1[0-9](.[0-9]+)?\|ms$/', $this->connection->getLastMessage());
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
        $client = new Client($this->connection, 'test', 0.3);
        $client->startTiming('foo.baz');
        usleep(10000);
        $client->endTiming('foo.baz');

        // ranges between 1000 and 1001ms
        $this->assertRegExp('/^test\.foo\.baz:1[0-9](.[0-9]+)?\|ms\|@0.3$/', $this->connection->getLastMessage());
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

    public function testGaugeWithTags()
    {
        $this->client->gauge("foobar", 333, array('tag' => 'value'));
        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.foobar:333|g|#tag:value', $message);
    }

    public function testGaugeCanReceiveFormattedNumber()
    {
        $this->client->gauge('foobar', '+11');

        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.foobar:+11|g', $message);
    }

    public function testSet()
    {
        $this->client->set("barfoo", 666);

        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.barfoo:666|s', $message);
    }

    public function testSetWithTags()
    {
        $this->client->set("barfoo", 666, array('tag' => 'value', 'tag2' => 'value2'));
        $message = $this->connection->getLastMessage();
        $this->assertEquals('test.barfoo:666|s|#tag:value, tag2:value2', $message);
    }
}
