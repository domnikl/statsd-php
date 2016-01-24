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
     * sends a message to the socket
     *
     * @param string $message
     *
     * @codeCoverageIgnore
     * this is ignored because it writes to an actual socket and is not testable
     */
    public function send($message)
    {
        try {
            parent::send($message);
        } catch (\Exception $e) {
            // ignore it: stats logging failure shouldn't stop the whole app
        }
    }

    /**
     * @param string $message
     */
    protected function writeToSocket($message)
    {
        // suppress all errors
        @fwrite($this->socket, $message);
    }

    /**
     * @param string $host
     * @param int $port
     * @param int|null $timeout
     * @param bool $persistent
     */
    protected function connect($host, $port, $timeout, $persistent = false)
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = sprintf("udp://%s", $host);

        if ($persistent) {
            $this->socket = @pfsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        } else {
            $this->socket = @fsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
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
