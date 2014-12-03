<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service in TCP mode
 *
 * @codeCoverageIgnore
 */
class TcpSocket extends InetSocket implements Connection
{
    /**
     * the used TCP socket resource
     *
     * @var resource|null|false
     */
    private $socket;

    /**
     * @param string $message
     */
    protected function writeToSocket($message)
    {
        fwrite($this->socket, $message);
    }

    protected function connect()
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = sprintf("tcp://%s", $this->host);

        if ($this->persistent) {
            $this->socket = pfsockopen($url, $this->port, $errorNumber, $errorMessage, $this->timeout);
        } else {
            $this->socket = fsockopen($url, $this->port, $errorNumber, $errorMessage, $this->timeout);
        }
    }

    /**
     * checks whether the socket connection is alive
     *
     * @return bool
     */
    protected function isConnected()
    {
        return is_resource($this->socket);
    }
}
