<?php

namespace Domnikl\Statsd\Connection;

abstract class InetSocket
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
     * @var int|null
     */
    private $timeout;

    /**
     * Persistent connection
     *
     * @var bool
     */
    private $persistent = false;

    /**
     * is sampling allowed?
     *
     * @var bool
     */
    private $forceSampling = false;

    /**
     * the used socket resource
     *
     * @var resource|null|false
     */
    protected $socket;

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
     * connect to statsd service
     *
     * @return resource|null
     *
     * @codeCoverageIgnore
     * this is ignored because it requires to open a real UDP socket
     */
    private function connect()
    {
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

        // only try once to connect to the socket, if this fails, socket will be false
        // and connect will not be run again, this saves some time waiting for the connect to
        // take place
        if ($this->socket === null) {
            $this->connect();
        }

        if (is_resource($this->socket)) {
            try {
                $this->writeToSocket($message);

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
    abstract protected function getProtocol();

    /**
     * @param string $message
     */
    abstract protected function writeToSocket($message);
}
