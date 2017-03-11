<?php

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    /**
     * @covers \Domnikl\Statsd\Connection\File::send()
     * @covers \Domnikl\Statsd\Connection\File::open()
     */
    public function testSend()
    {
        $metric = 'file.test.namespace.customer.signed_up:1|c';
        $connection = new File('php://memory');
        $connection->send($metric);

        $handle = $this->getFileHandle($connection);
        rewind($handle);
        $this->assertEquals($metric . PHP_EOL, stream_get_contents($handle));
    }

    /**
     * @param mixed $metric
     * @covers \Domnikl\Statsd\Connection\File::send()
     * @dataProvider dataForSendWrongData
     */
    public function testSendWrongData($metric)
    {
        $connection = new File('php://memory');
        $connection->send($metric);
        $this->assertNull($this->getFileHandle($connection));
    }

    /**
     * @return array
     */
    public function dataForSendWrongData()
    {
        return [
            [null],
            [''],
            [123],
            [124.5],
            [new \stdClass()],
        ];
    }

    /**
     * @covers \Domnikl\Statsd\Connection\File::sendMessages()
     */
    public function testSendMessages()
    {
        $metrics = [
            'file.test.namespace.customer.signed_up:1|c',
            'file.test.namespace.products.viewed:8|c',
            'file.test.namespace.timing.while:2010.7848644257|ms',
            'file.test.namespace.batch:1|c',
        ];

        $connection = new File('php://memory');
        $connection->sendMessages($metrics);
        $handle = $this->getFileHandle($connection);
        rewind($handle);
        $this->assertEquals(implode(PHP_EOL, $metrics) . PHP_EOL, stream_get_contents($handle));
    }

    /**
     * @param File $file
     * @return resource|null
     */
    private function getFileHandle(File $file)
    {
        $reflector = new \ReflectionClass($file);
        $reflectorProperty = $reflector->getProperty('handle');
        $reflectorProperty->setAccessible(true);
        return $reflectorProperty->getValue($file);
    }
}
