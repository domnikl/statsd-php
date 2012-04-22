<?php

/**
 *
 * @author Dominik Liebler <liebler.dominik@googlemail.com>
 */
class Statsd
{
    /**
     * the host to connect to
     *
     * @var string
     */
    protected $_host;

    /**
     * the port to connect to
     *
     * @var int
     */
    protected $_port;

    /**
     * holds all the timings that have not yet been completed
     *
     * @var array
     */
    protected $_timings = array();

    /**
     * key namespace
     *
     * @var string
     */
    protected $_namespace = '';

    /**
     * inits the Statsd object, but does not yet create a socket
     *
     * @param string $host
     * @param int $port
     * @param string $namespace global key namespace
     */
    public function __construct($host = 'localhost', $port = 8125, $namespace = '')
    {
        $this->_host = (string) $host;
        $this->_port = (int) $port;
        $this->_namespace = (string) $namespace;
    }

    /**
     * increments the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     *
     * @return string the sent message
     */
    public function increment($key, $sampleRate = 1)
    {
        return $this->count($key, 1, $sampleRate);
    }

    /**
     * decrements the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     *
     * @return string the sent message
     */
    public function decrement($key, $sampleRate = 1)
    {
        return $this->count($key, -1, $sampleRate);
    }
    /**
     * sends a count to statsd
     *
     * @param string $key
     * @param int $value
     * @param int $sampleRate (optional) the default is 1
     *
     * @return string the sent message
     */
    public function count($key, $value, $sampleRate = 1)
    {
        return $this->_send($key, (int) $value, 'c', $sampleRate);
    }

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param int $value the timing in ms
     * @param int $sampleRate the sample rate, if < 1, statsd will send an average timing
     *
     * @return string the sent message
     */
    public function timing($key, $value, $sampleRate = 1)
    {
        return $this->_send($key, (int) $value, 'ms', $sampleRate);
    }

    /**
     * starts the timing for a key
     *
     * @param string $key
     *
     * @return void
     */
    public function startTiming($key)
    {
        $this->_timings[$key] = gettimeofday(true);
    }

    /**
     * ends the timing for a key and sends it to statsd
     *
     * @param string $key
     * @param int $sampleRate (optional)
     *
     * @return string the sent message
     */
    public function endTiming($key, $sampleRate = 1)
    {
        $end = gettimeofday(true);
        $message = '';

        if (array_key_exists($key, $this->_timings)) {
            $timing = ($end - $this->_timings[$key]) * 1000;
            $message = $this->timing($key, $timing, $sampleRate);
            unset($this->_timings[$key]);
        }

        return $message;
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
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $samplingRate
     *
     * @return string
     */
    protected function _send($key, $value, $type, $samplingRate)
    {
        if (0 != strlen($this->_namespace)) {
            $key = sprintf('%s.%s', $this->_namespace, $key);
        }

        $message = sprintf("%s:%d|%s", $key, $value, $type);

        if ($samplingRate != 1) {
            $message .= '|@' . (1 / $samplingRate);
        }

        $socket = fsockopen(sprintf("udp://%s", $this->_host), $this->_port);

        if ($socket) {
            fwrite($socket, $message);
            fclose($socket);
        }

        return $message;
    }

    /**
     * changes the global key namespace
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = (string) $namespace;
    }

    /**
     * gets the global key namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * gets the configured host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * gets the configured port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }
}
