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
     * Tags style
     *
     * @var string
     */
    private $tagsStyle = 'dogstatsd';

    /**
     * Tags style format
     *
     * metric.dogstatsd:123|c|@0.01|#tagName:val,tag2:val2
     * https://docs.datadoghq.com/developers/dogstatsd/datagram_shell/?tab=metrics
     *
     * metric.graphite;tag1=val;tag2=val2:123|c|@0.01
     * https://graphite.readthedocs.io/en/latest/tags.html#carbon
     *
     * metric.influxdb,tag1=val,tag2=val2:123|c|@0.01
     * https://www.influxdata.com/blog/getting-started-with-sending-statsd-metrics-to-telegraf-influxdb/
     *
     * metric.signalfx[tag1=val,tag2=val2]:123|c|@0.01
     * https://docs.signalfx.com/en/latest/integrations/agent/monitors/collectd-statsd.html
     *
     * metric.librato#tag1=val,tag2=val2:123|c|@0.01
     * https://github.com/librato/statsd-librato-backend#tags
     *
     * style                position   beforeGlue  afterGlue  pairsGlue  keyValueGlue
     * dogstatsd (datadog)  END        |#                     ,          :
     * graphite             AFTER_KEY  ;                      ;          =
     * influxdb (telegraf)  AFTER_KEY  ,                      ,          =
     * signalfx             AFTER_KEY  [           ]          ,          =
     * librato              AFTER_KEY  #                      ,          =
     *
     */
    private const TAGS_STYLE_FORMAT = [
        'dogstatsd' => [
            'position'     => 'END',
            'beforeGlue'   => '|#',
            'afterGlue'    => '',
            'pairsGlue'    => ',',
            'keyValueGlue' => ':',
        ],
        'graphite' => [
            'position'     => 'AFTER_KEY',
            'beforeGlue'   => ';',
            'afterGlue'    => '',
            'pairsGlue'    => ';',
            'keyValueGlue' => '=',
        ],
        'influxdb' => [
            'position'     => 'AFTER_KEY',
            'beforeGlue'   => ',',
            'afterGlue'    => '',
            'pairsGlue'    => ',',
            'keyValueGlue' => '=',
        ],
        'signalfx' => [
            'position'     => 'AFTER_KEY',
            'beforeGlue'   => '[',
            'afterGlue'    => ']',
            'pairsGlue'    => ',',
            'keyValueGlue' => '=',
        ],
        'librato' => [
            'position'     => 'AFTER_KEY',
            'beforeGlue'   => '#',
            'afterGlue'    => '',
            'pairsGlue'    => ',',
            'keyValueGlue' => '=',
        ],
    ];

    /**
     * initializes the client object
     *
     * @param Connection $connection
     * @param string $namespace global key namespace
     * @param float $sampleRateAllMetrics if set to a value <1, all metrics will be sampled using this rate
     * @param string $tagsStyle tags style (see all variants in TAGS_STYLE_FORMAT)
     * @throws \Exception
     */
    public function __construct(
        Connection $connection,
        string $namespace = '',
        float $sampleRateAllMetrics = 1.0,
        string $tagsStyle = 'dogstatsd'
    ) {
        $this->connection = $connection;
        $this->namespace = $namespace;
        $this->sampleRateAllMetrics = $sampleRateAllMetrics;
        $this->checkTagsStyle($tagsStyle);
        $this->tagsStyle = $tagsStyle;
    }

    /**
     * check tags style support
     * @param string $tagsStyle
     * @throws \Exception
     */
    private function checkTagsStyle(string $tagsStyle): void
    {
        if (!isset(self::TAGS_STYLE_FORMAT[$tagsStyle])) {
            $errorMessage = sprintf(
                "Tags style '%s' is not supported. Available styles: %s",
                $tagsStyle,
                implode(', ', array_keys(self::TAGS_STYLE_FORMAT))
            );
            throw new \Exception($errorMessage);
        }
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

        if (strlen($this->namespace) !== 0) {
            $key = $this->namespace . '.' . $key;
        }

        $tagsFormat = self::TAGS_STYLE_FORMAT[$this->tagsStyle];

        $tagsString = '';
        if (!empty($tags)) {
            $tagsString = $tagsFormat['beforeGlue'] . implode(
                $tagsFormat['pairsGlue'],
                array_map(
                    function ($k, $v) use ($tagsFormat) {
                        return $k . $tagsFormat['keyValueGlue'] . $v;
                    },
                    array_keys($tags),
                    $tags
                )
            ) . $tagsFormat['afterGlue'];
        }

        $sampledData =
            $key .
            ($tagsFormat['position'] == 'AFTER_KEY' ?  $tagsString : '') .
            ':' . $value .
            '|' . $type .
            ($sampleRate < 1 ? '|@' . $sampleRate : '') .
            ($tagsFormat['position'] == 'END'       ?  $tagsString : '')
        ;

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
     * changes the statsd tags style implementation
     *
     * @param string $tagsStyle
     * @throws \Exception
     */
    public function setTagsStyle(string $tagsStyle): void
    {
        $this->checkTagsStyle($tagsStyle);
        $this->tagsStyle = $tagsStyle;
    }

    /**
     * gets the statsd tags style implementation
     *
     * @return string
     */
    public function getTagsStyle(): string
    {
        return $this->tagsStyle;
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
