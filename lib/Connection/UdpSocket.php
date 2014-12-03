<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service in UDP mode (standard)
 *
 * @codeCoverageIgnore
 */
class UdpSocket extends InetSocket implements Connection
{
    /**
     * the used UDP socket resource
     *
     * @var resource|null|false
     */
    private $socket;

    /**
     * @var bool
     */
    private $isConnected;

    /**
     * @param string $message
     */
    protected function writeToSocket($message)
    {
        // suppress all errors
        @fwrite($this->socket, $message);
    }

    protected function connect()
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = sprintf("udp://%s", $this->host);

        if ($this->persistent) {
            $this->socket = @pfsockopen($url, $this->port, $errorNumber, $errorMessage, $this->timeout);
        } else {
            $this->socket = @fsockopen($url, $this->port, $errorNumber, $errorMessage, $this->timeout);
        }

        $this->isConnected = true;
    }

    /**
     * checks whether the socket connection is alive
     *
     * only tries to connect once
     *
     * ever after isConnected will return true,
     * because $this->socket is then false
     *
     * @return bool
     */
    protected function isConnected()
    {
        return $this->isConnected;
    }
}
