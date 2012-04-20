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
     * inits the Statsd object, but does not yet create a socket
     *
     * @param string $host
     * @param int $port
     */
    public function __construct($host = 'localhost', $port = 8125)
    {
        $this->_host = $host;
        $this->_port = (int) $port;
    }

    /**
     * increments the key by 1
     *
     * @param string $key
     * @param int $sampleRate
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function count($key, $value, $sampleRate = 1)
    {
        $this->_send($key, (int) $value, 'c', $sampleRate);
    }

    /**
     * sends a timing to statsd (in ms)
     *
     * @param string $key
     * @param int $value the timing in ms
     * @param int $sampleRate the sample rate, if < 1, statsd will send an average timing
     *
     * @return void
     */
    public function timing($key, $value, $sampleRate = 1)
    {
        $this->_send($key, (int) $value, 'ms', $sampleRate);
    }

    /**
     * executes a Closure and records it's execution time and sends it to statsd
     * returns the value the Closure returned
     *
     * @param string $key
     * @param Closure $_block
     * @param int $samplingRate (optional) default = 1
     *
     * @return mixed
     */
    public function time($key, Closure $_block, $samplingRate = 1)
    {
        $start = gettimeofday(true);
        $return = $_block();
        $finish = gettimeofday(true);

        $ms = ($finish - $start) * 1000;

        $this->_send($key, $ms, 'ms', $samplingRate);

        return $return;
    }

    /**
     * actually sends a message to to the daemon
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $samplingRate
     *
     * @return void
     */
    protected function _send($key, $value, $type, $samplingRate)
    {
        $message = sprintf("%s:%d|%s", $key, $value, $type);

        if ($samplingRate != 1) {
            $message .= '|@' . (1 / $samplingRate);
        }

        $socket = fsockopen(sprintf("udp://%s", $this->_host), $this->_port);

        if ($socket) {
            fwrite($socket, $message);
            fclose($socket);
        }
    }
}
