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
        fwrite($this->socket, $message . "\r\n");
    }

    /**
     * @param string $host
     * @param int $port
     * @param int|null $timeout
     * @param bool $persistent
     */
    protected function connect($host, $port, $timeout, $persistent)
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = sprintf("tcp://%s", $host);

        if ($persistent) {
            $this->socket = pfsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        } else {
            $this->socket = fsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
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
