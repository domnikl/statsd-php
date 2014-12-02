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
     * @return string
     */
    protected function getProtocol()
    {
        return 'tcp';
    }

    /**
     * @param string $message
     */
    protected function writeToSocket($message)
    {
        // suppress all errors
        @fwrite($this->socket, $message);
    }
}
