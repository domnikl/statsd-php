<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service
 *
 * @author Dominik Liebler <liebler.dominik@googlemail.com>
 */
class Socket implements Connection
{
    /**
     * host name
     *
     * @var string
     */
    private $host;

    /**
     * port number
     *
     * @var int
     */
    private $port;

    /**
     * Socket timeout
     *
     * @var int
     */
    private $timeout;

    /**
     * Persistent connection
     *
     * @var bool
     */
    private $persistent = false;

    /**
     * the used socket resource
     *
     * @var resource
     */
    private $socket;

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
        $this->timeout = $timeout;
        $this->persistent = $persistent;
    }

    /**
     * connect to statsd service
     */
    protected function connect()
    {
        // TODO: Why these??
        $errorNumber = null;
        $errorMessage = null;

        if ($this->persistent) {
            $this->socket = pfsockopen(sprintf("udp://%s", $this->host), $this->port, $errorNumber, $errorMessage, $this->timeout);
        } else {
            $this->socket = fsockopen(sprintf("udp://%s", $this->host), $this->port, $errorNumber, $errorMessage, $this->timeout);
        }
    }

    /**
     * sends a message to the UDP socket
     *
     * @param string $message
     */
    public function send($message)
    {
        if (!$this->socket) {
            $this->connect();
        }

        if (0 != strlen($message) && $this->socket) {
            try {
                // total suppression of errors
                @fwrite($this->socket, $message);
            } catch (\Exception $e) {
                // ignore it: stats logging failure shouldn't stop the whole app
            }
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
}
