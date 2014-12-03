<?php

namespace Domnikl\Statsd\Connection;

abstract class InetSocket
{
    /**
     * host name
     *
     * @var string
     */
    protected $host;

    /**
     * port number
     *
     * @var int
     */
    protected $port;

    /**
     * Socket timeout
     *
     * @var int|null
     */
    protected $timeout;

    /**
     * Persistent connection
     *
     * @var bool
     */
    protected $persistent = false;

    /**
     * is sampling allowed?
     *
     * @var bool
     */
    private $forceSampling = false;

    /**
     * instantiates the Connection object and a real connection to statsd
     *
     * @param string $host Statsd hostname
     * @param int $port Statsd port
     * @param int $timeout Connection timeout
     * @param bool $persistent (default FALSE) Use persistent connection or not
     */
    public function __construct($host = 'localhost', $port = 8125, $timeout = null, $persistent = false)
    {
        $this->host = (string) $host;
        $this->port = (int) $port;

        $this->persistent = (bool) $persistent;

        if ($timeout !== null) {
            $this->timeout = (int) $timeout;
        } else {
            $this->timeout = null;
        }
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return bool
     */
    public function isPersistent()
    {
        return $this->persistent;
    }

    /**
     * is sampling forced?
     *
     * @return boolean
     */
    public function forceSampling()
    {
        return (bool) $this->forceSampling;
    }

    /**
     * sends a message to the UDP socket
     *
     * @param string $message
     *
     * @codeCoverageIgnore
     * this is ignored because it writes to an actual socket and is not testable
     */
    public function send($message)
    {
        // prevent from sending empty or non-sense metrics
        if (!is_string($message) || $message == '') {
            return;
        }

        try {
            if (!$this->isConnected()) {
                $this->connect();
            }

            $this->writeToSocket($message);
        } catch (\Exception $e) {
            // ignore it: stats logging failure shouldn't stop the whole app
        }
    }

    /**
     * connect to the socket
     *
     * @return mixed
     */
    abstract protected function connect();

    /**
     * checks whether the socket connection is alive
     *
     * @return bool
     */
    abstract protected function isConnected();

    /**
     * writes a message to the socket
     *
     * @param string $message
     */
    abstract protected function writeToSocket($message);
}
