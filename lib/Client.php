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
    private $timings = [];

    /**
     * holds all memory profiles like timings
     *
     * @var array
     */
    private $memoryProfiles = [];

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
    private $batch = [];

    /**
     * batch mode?
     *
     * @var boolean
     */
    private $isBatch = false;

    /**
     * @var bool
     */
    private $sampleRateAllMetrics = 1;

    /**
     * initializes the client object
     *
     * @param Connection $connection
     * @param string $namespace global key namespace
     * @param float $sampleRateAllMetrics if set to a value <1, all metrics will be sampled using this rate
     */
    public function __construct(Connection $connection, $namespace = '', $sampleRateAllMetrics = 1.0)
    {
        $this->connection = $connection;
        $this->namespace = (string) $namespace;
        $this->sampleRateAllMetrics = (float) $sampleRateAllMetrics;
    }

    /**
     * increments the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     * @param array $tags
     */
    public function increment($key, $sampleRate = 1, $tags = [])
    {
        $this->count($key, 1, $sampleRate, $tags);
    }

    /**
     * decrements the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     * @param array $tags
     */
    public function decrement($key, $sampleRate = 1, $tags = []
    {
        $this->count($key, -1, $sampleRate, $tags);
    }
    /**
     * sends a count to statsd
     *
     * @param string $key
     * @param int $value
     * @param int $sampleRate (optional) the default is 1
     * @param array $tags
     */
    public function count($key, $value, $sampleRate = 1, $tags = []
    {
        $this->send($key, (int) $value, 'c', $sampleRate, $tags);
    }

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param int $value the timing in ms
     * @param int $sampleRate the sample rate, if < 1, statsd will send an average timing
     * @param array $tags
     */
    public function timing($key, $value, $sampleRate = 1, $tags = []
    {
        $this->send($key, $value, 'ms', $sampleRate, $tags);
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
        if ($memory === null) {
            $memory = memory_get_peak_usage();
        }

        $this->count($key, $memory, $sampleRate);
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
        try {
            return $_block();
        } finally {
            $this->endTiming($key, $sampleRate);
        }
    }

    /**
     * sends a gauge, an arbitrary value to StatsD
     *
     * @param string $key
     * @param string|int $value
     * @param array $tags
     */
    public function gauge($key, $value, $tags = []
    {
        $this->send($key, $value, 'g', 1, $tags);
    }

    /**
     * sends a set member
     *
     * @param string $key
     * @param int $value
     * @param array $tags
     */
    public function set($key, $value, $tags = []
    {
        $this->send($key, $value, 's', 1, $tags);
    }

    /**
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $sampleRate
     * @param array $tags
     */
    private function send($key, $value, $type, $sampleRate, $tags = []
    {
        if (mt_rand() / mt_getrandmax() > $sampleRate) {
            return;
        }

        if (strlen($this->namespace) !== 0) {
            $key = $this->namespace . '.' . $key;
        }

        $message = $key . ':' . $value . '|' . $type;

        // overwrite sampleRate if all metrics should be sampled
        if ($this->sampleRateAllMetrics < 1) {
            $sampleRate = $this->sampleRateAllMetrics;
        }

        if ($sampleRate < 1) {
            $sampledData = $message . '|@' . $sampleRate;
        } else {
            $sampledData = $message;
        }

        if (!empty($tags)) {
            $sampledData .= '|#';
            $tagArray = [];
            foreach($tags as $key => $value) {
              $tagArray[] = ($key . ':' . $value);
            }
            $sampledData .= join(',', $tagArray);
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
        $this->connection->sendMessages($this->batch);
        $this->batch = [];
    }

    /**
     * stops batch-recording and resets the batch
     */
    public function cancelBatch()
    {
        $this->isBatch = false;
        $this->batch = [];
    }
}
