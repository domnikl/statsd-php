<?php

namespace Domnikl\Statsd;

/**
 * Contract for the statsd client
 */
interface ClientInterface
{
    /**
     * increments the key by 1
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function increment($key, $sampleRate = 1.0, $tags = []);

    /**
     * decrements the key by 1
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function decrement($key, $sampleRate = 1.0, $tags = []);

    /**
     * sends a count to statsd
     *
     * @param string $key
     * @param int|float $value
     * @param float $sampleRate
     * @param array $tags
     */
    public function count($key, $value, $sampleRate = 1.0, $tags = []);

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param int $value the timing in ms
     * @param float $sampleRate
     * @param array $tags
     */
    public function timing($key, $value, $sampleRate = 1.0, $tags = []);

    /**
     * starts the timing for a key
     *
     * @param string $key
     */
    public function startTiming($key);

    /**
     * ends the timing for a key and sends it to statsd
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     *
     * @return float|null
     */
    public function endTiming($key, $sampleRate = 1.0, $tags = []);

    /**
     * start memory "profiling"
     *
     * @param string $key
     */
    public function startMemoryProfile($key);

    /**
     * ends the memory profiling and sends the value to the server
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function endMemoryProfile($key, $sampleRate = 1.0, $tags = []);

    /**
     * report memory usage to statsd. if memory was not given report peak usage
     *
     * @param string $key
     * @param int $memory
     * @param float $sampleRate
     * @param array $tags
     */
    public function memory($key, $memory = null, $sampleRate = 1.0, $tags = []);

    /**
     * executes a Closure and records it's execution time and sends it to statsd
     * returns the value the Closure returned
     *
     * @param string $key
     * @param \Closure $block
     * @param float $sampleRate
     * @param array $tags
     *
     * @return mixed
     */
    public function time($key, \Closure $block, $sampleRate = 1.0, $tags = []);

    /**
     * sends a gauge, an arbitrary value to StatsD
     *
     * @param string $key
     * @param string|int $value
     * @param array $tags
     */
    public function gauge($key, $value, $tags = []);

    /**
     * sends a set member
     *
     * @param string $key
     * @param int $value
     * @param array $tags
     */
    public function set($key, $value, $tags = []);

    /**
     * changes the global key namespace
     *
     * @param string $namespace
     */
    public function setNamespace($namespace);

    /**
     * gets the global key namespace
     *
     * @return string
     */
    public function getNamespace();

    /**
     * is batch processing running?
     *
     * @return boolean
     */
    public function isBatch();

    /**
     * start batch-send-recording
     */
    public function startBatch();

    /**
     * ends batch-send-recording and sends the recorded messages to the connection
     */
    public function endBatch();

    /**
     * stops batch-recording and resets the batch
     */
    public function cancelBatch();
}
