<?php declare(strict_types=1);

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
     * @var float
     */
    private $sampleRateAllMetrics = 1.0;

    /**
     * initializes the client object
     *
     * @param Connection $connection
     * @param string $namespace global key namespace
     * @param float $sampleRateAllMetrics if set to a value <1, all metrics will be sampled using this rate
     */
    public function __construct(Connection $connection, string $namespace = '', float $sampleRateAllMetrics = 1.0)
    {
        $this->connection = $connection;
        $this->namespace = $namespace;
        $this->sampleRateAllMetrics = $sampleRateAllMetrics;
    }

    /**
     * increments the key by 1
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function increment(string $key, float $sampleRate = 1.0, array $tags = []): void
    {
        $this->count($key, 1, $sampleRate, $tags);
    }

    /**
     * decrements the key by 1
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function decrement(string $key, float $sampleRate = 1.0, array $tags = []): void
    {
        $this->count($key, -1, $sampleRate, $tags);
    }
    /**
     * sends a count to statsd
     *
     * @param string $key
     * @param int|float $value
     * @param float $sampleRate
     * @param array $tags
     */
    public function count(string $key, $value, float $sampleRate = 1.0, array $tags = []): void
    {
        $this->send($key, $value, 'c', $sampleRate, $tags);
    }

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param float $value the timing in ms
     * @param float $sampleRate
     * @param array $tags
     */
    public function timing(string $key, float $value, float $sampleRate = 1.0, array $tags = []): void
    {
        $this->send($key, $value, 'ms', $sampleRate, $tags);
    }

    /**
     * starts the timing for a key
     *
     * @param string $key
     */
    public function startTiming(string $key): void
    {
        $this->timings[$key] = gettimeofday(true);
    }

    /**
     * ends the timing for a key and sends it to statsd
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     *
     * @return float|null
     */
    public function endTiming(string $key, float $sampleRate = 1.0, array $tags = []): ?float
    {
        $end = gettimeofday(true);

        if (isset($this->timings[$key])) {
            $timing = ($end - $this->timings[$key]) * 1000;
            $this->timing($key, $timing, $sampleRate, $tags);
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
    public function startMemoryProfile(string $key): void
    {
        $this->memoryProfiles[$key] = memory_get_usage();
    }

    /**
     * ends the memory profiling and sends the value to the server
     *
     * @param string $key
     * @param float $sampleRate
     * @param array $tags
     */
    public function endMemoryProfile(string $key, float $sampleRate = 1.0, array $tags = []): void
    {
        $end = memory_get_usage();

        if (array_key_exists($key, $this->memoryProfiles)) {
            $memory = ($end - $this->memoryProfiles[$key]);
            $this->memory($key, $memory, $sampleRate, $tags);

            unset($this->memoryProfiles[$key]);
        }
    }

    /**
     * report memory usage to statsd. if memory was not given report peak usage
     *
     * @param string $key
     * @param int $memory
     * @param float $sampleRate
     * @param array $tags
     */
    public function memory(string $key, int $memory = null, float $sampleRate = 1.0, array $tags = []): void
    {
        if ($memory === null) {
            $memory = memory_get_peak_usage();
        }

        $this->count($key, $memory, $sampleRate, $tags);
    }

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
    public function time(string $key, \Closure $block, float $sampleRate = 1.0, array $tags = [])
    {
        $this->startTiming($key);
        try {
            return $block();
        } finally {
            $this->endTiming($key, $sampleRate, $tags);
        }
    }

    /**
     * sends a gauge, an arbitrary value to StatsD
     *
     * @param string $key
     * @param string|int $value
     * @param array $tags
     */
    public function gauge(string $key, $value, array $tags = []): void
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
    public function set(string $key, int $value, array $tags = []): void
    {
        $this->send($key, $value, 's', 1, $tags);
    }

    /**
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int|float|string $value
     * @param string $type
     * @param float $sampleRate
     * @param array $tags
     */
    private function send(string $key, $value, string $type, float $sampleRate, array $tags = []): void
    {
        // override sampleRate if all metrics should be sampled
        if ($this->sampleRateAllMetrics < 1) {
            $sampleRate = $this->sampleRateAllMetrics;
        }

        if ($sampleRate < 1 && mt_rand() / mt_getrandmax() > $sampleRate) {
            return;
        }

        $sampledData = $this->buildSampledData($key, $value, $type, $sampleRate, $tags);

        if (!$this->isBatch) {
            $this->connection->send($sampledData);
        } else {
            $this->batch[] = $sampledData;
        }
    }

    /**
     * Prepares a statsd compatible string from given data and parameters
     *
     * @param string $key
     * @param int|float|string $value
     * @param string $type
     * @param float $sampleRate
     * @param array $tags
     * @return string $sampledData
     */
    public function buildSampledData(string $key, $value, string $type, float $sampleRate, array $tags = []): string
    {
        $sampledData = null;

        if (strlen($this->namespace) !== 0) {
            $key = $this->namespace . '.' . $key;
        }

        $message = $key . ':' . $value . '|' . $type;

        if ($sampleRate < 1) {
            $sampledData = $message . '|@' . $sampleRate;
        } else {
            $sampledData = $message;
        }

        if (!empty($tags)) {
            $sampledData .= '|#';
            $tagArray = [];

            foreach ($tags as $key => $value) {
                $tagArray[] = ($key . ':' . $value);
            }

            $sampledData .= join(',', $tagArray);
        }

        return $sampledData;
    }

    /**
     * changes the global key namespace
     *
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = (string) $namespace;
    }

    /**
     * gets the global key namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * is batch processing running?
     *
     * @return bool
     */
    public function isBatch(): bool
    {
        return $this->isBatch;
    }

    /**
     * start batch-send-recording
     */
    public function startBatch(): void
    {
        $this->isBatch = true;
    }

    /**
     * ends batch-send-recording and sends the recorded messages to the connection
     */
    public function endBatch(): void
    {
        $this->isBatch = false;
        $this->connection->sendMessages($this->batch);
        $this->batch = [];
    }

    /**
     * stops batch-recording and resets the batch
     */
    public function cancelBatch(): void
    {
        $this->isBatch = false;
        $this->batch = [];
    }
}
