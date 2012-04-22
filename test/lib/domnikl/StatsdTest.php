<?php

namespace Domnikl\test;

require __DIR__ . '/../../../lib/Domnikl/Statsd.php';

use Domnikl\Statsd as Statsd;

class StatsdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Domnikl\Statsd
     */
    protected $_client;


    public function setUp()
    {
        $this->_client = new Statsd('localhost', 8125, 'test');
    }

    public function testInit()
    {
        $client = new Statsd('foo.bar.baz', 100);

        $this->assertEquals('foo.bar.baz', $client->getHost());
        $this->assertEquals(100, $client->getPort());
        $this->assertEquals('', $client->getNamespace());
    }

    public function testNamespace()
    {
        $client = new Statsd('localhost', 8125, 'test.foo');
        $this->assertEquals('test.foo', $client->getNamespace());

        $client->setNamespace('bar.baz');
        $this->assertEquals('bar.baz', $client->getNamespace());
    }

    public function testCount()
    {
        $this->assertEquals(
            'test.foo.bar:100|c',
            $this->_client->count('foo.bar', 100)
        );
    }

    /**
     * @group sampling
     */
    public function testCountWithSamplingRate()
    {
        $this->assertEquals(
            'test.foo.bar:100|c|@0.1',
            $this->_client->count('foo.bar', 100, 10)
        );
    }

    public function testIncrement()
    {
        $this->assertEquals(
            'test.foo.baz:1|c',
            $this->_client->increment('foo.baz')
        );
    }

    /**
     * @group sampling
     */
    public function testIncrementWithSamplingRate()
    {
        $this->assertEquals(
            'test.foo.baz:1|c|@0.01',
            $this->_client->increment('foo.baz', 100)
        );
    }

    public function testDecrement()
    {
        $this->assertEquals(
            'test.foo.baz:-1|c',
            $this->_client->decrement('foo.baz')
        );
    }

    /**
     * @group sampling
     */
    public function testDecrementWithSamplingRate()
    {
        $this->assertEquals(
            'test.foo.baz:-1|c|@0.05',
            $this->_client->decrement('foo.baz', 20)
        );
    }

    public function testTiming()
    {
        $this->assertEquals(
            'test.foo.baz:2000|ms',
            $this->_client->timing('foo.baz', 2000)
        );
    }


    /**
     * @group sampling
     */
    public function testTimingWithSamplingRate()
    {
        $this->assertEquals(
            'test.foo.baz:2000|ms|@0.1',
            $this->_client->timing('foo.baz', 2000, 10)
        );
    }

    public function testStartEndTiming()
    {
        $key = 'foo.bar';
        $this->_client->startTiming($key);
        sleep(1);
        $message = $this->_client->endTiming($key);

        // ranges between 1000 and 1001ms
        $this->assertRegExp('/test\.foo\.bar:100[0|1]{1}|ms/', $message);
    }

    /**
     * @group sampling
     */
    public function testStartEndTimingWithSamplingRate()
    {
        $this->_client->startTiming('foo.baz');
        sleep(1);
        $message = $this->_client->endTiming('foo.baz', 10);

        // ranges between 1000 and 1001ms
        $this->assertRegExp('/test\.foo\.baz:100[0|1]{1}|ms|@0.1/', $message);
    }

    public function testTimeClosure()
    {
        $evald = $this->_client->time('foo', function() {
            return "foobar";
        });

        $this->assertEquals('foobar', $evald);
    }
}
