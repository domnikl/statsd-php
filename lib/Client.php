<?php

namespace Domnikl\Statsd;

/**
 * the statsd client
 */
class Client
{
    /**
     * Connection object that messages get send to
     *
     * @var Connection
     */
    private $connection;

    /**
     * holds all the timings that have not yet been completed
     *
     * @var array
     */
    private $timings = array();

    /**
     * holds all memory profiles like timings
     *
     * @var array
     */
    private $memoryProfiles = array();

    /**
     * global key namespace
     *
     * @var string
     */
    private $namespace = '';

    /**
     * stores the batch after batch processing was started
     *
     * @var array
     */
    private $batch = array();

    /**
     * batch mode?
     *
     * @var boolean
     */
    private $isBatch = false;

    /**
     * inits the client object
     *
     * @param Connection $connection
     * @param string $namespace global key namespace
     */
    public function __construct(Connection $connection, $namespace = '')
    {
        $this->connection = $connection;
        $this->namespace = (string) $namespace;
    }

    /**
     * increments the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     */
    public function increment($key, $sampleRate = 1)
    {
        $this->count($key, 1, $sampleRate);
    }

    /**
     * decrements the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     */
    public function decrement($key, $sampleRate = 1)
    {
        $this->count($key, -1, $sampleRate);
    }
    /**
     * sends a count to statsd
     *
     * @param string $key
     * @param int $value
     * @param int $sampleRate (optional) the default is 1
     */
    public function count($key, $value, $sampleRate = 1)
    {
        $this->send($key, (int) $value, 'c', $sampleRate);
    }

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param int $value the timing in ms
     * @param int $sampleRate the sample rate, if < 1, statsd will send an average timing
     */
    public function timing($key, $value, $sampleRate = 1)
    {
        $this->send($key, (int) $value, 'ms', $sampleRate);
    }

    /**
     * starts the timing for a key
     *
     * @param string $key
     */
    public function startTiming($key)
    {
        $this->timings[$key] = gettimeofday(true);
    }

    /**
     * ends the timing for a key and sends it to statsd
     *
     * @param string $key
     * @param int $sampleRate (optional)
     *
     * @return float|null
     */
    public function endTiming($key, $sampleRate = 1)
    {
        $end = gettimeofday(true);

        if (isset($this->timings[$key])) {
            $timing = ($end - $this->timings[$key]) * 1000;
            $this->timing($key, $timing, $sampleRate);
            unset($this->timings[$key]);

            return $timing;
        }

        return null;
    }

    /**
     * start memory "profiling"
     *
     * @param string $key
     */
    public function startMemoryProfile($key)
    {
        $this->memoryProfiles[$key] = memory_get_usage();
    }

    /**
     * ends the memory profiling and sends the value to the server
     *
     * @param string $key
     * @param int $sampleRate
     */
    public function endMemoryProfile($key, $sampleRate = 1)
    {
        $end = memory_get_usage();

        if (array_key_exists($key, $this->memoryProfiles)) {
            $memory = ($end - $this->memoryProfiles[$key]);
            $this->memory($key, $memory, $sampleRate);

            unset($this->memoryProfiles[$key]);
        }
    }

    /**
     * report memory usage to statsd. if memory was not given report peak usage
     *
     * @param string $key
     * @param int $memory
     * @param int $sampleRate
     */
    public function memory($key, $memory = null, $sampleRate = 1)
    {
        if (null === $memory) {
            $memory = memory_get_peak_usage();
        }

        $this->count($key, (int) $memory, $sampleRate);
    }

    /**
     * executes a Closure and records it's execution time and sends it to statsd
     * returns the value the Closure returned
     *
     * @param string $key
     * @param \Closure $_block
     * @param int $sampleRate (optional) default = 1
     *
     * @return mixed
     */
    public function time($key, \Closure $_block, $sampleRate = 1)
    {
        $this->startTiming($key);
        $return = $_block();
        $this->endTiming($key, $sampleRate);

        return $return;
    }

    /**
     * sends a gauge, an arbitrary value to StatsD
     *
     * @param string $key
     * @param int $value
     */
    public function gauge($key, $value)
    {
        $this->send($key, (int) $value, 'g', 1);
    }

    /**
     * sends a set member
     *
     * @param string $key
     * @param int $value
     */
    public function set($key, $value)
    {
        $this->send($key, $value, 's', 1);
    }

    /**
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $sampleRate
     */
    private function send($key, $value, $type, $sampleRate)
    {
        if (0 != strlen($this->namespace)) {
            $key = sprintf('%s.%s', $this->namespace, $key);
        }

        $message = sprintf("%s:%d|%s", $key, $value, $type);
        $sample = mt_rand() / mt_getrandmax();

        if ($sample > $sampleRate) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        if ($sampleRate < 1 || $this->connection->forceSampling()) {
            $sampledData = sprintf('%s|@%s', $message, $sampleRate);
        } else {
            $sampledData = $message;
        }

        if (!$this->isBatch) {
            $this->connection->send($sampledData);
        } else {
            $this->batch[] = $sampledData;
        }
    }

    /**
     * changes the global key namespace
     *
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string) $namespace;
    }

    /**
     * gets the global key namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * is batch processing running?
     *
     * @return boolean
     */
    public function isBatch()
    {
        return $this->isBatch;
    }

    /**
     * start batch-send-recording
     */
    public function startBatch()
    {
        $this->isBatch = true;
    }

    /**
     * ends batch-send-recording and sends the recorded messages to the connection
     */
    public function endBatch()
    {
        $this->isBatch = false;
        $this->connection->send(join("\n", $this->batch));
        $this->batch = array();
    }

    /**
     * stops batch-recording and resets the batch
     */
    public function cancelBatch()
    {
        $this->isBatch = false;
        $this->batch = array();
    }
}
