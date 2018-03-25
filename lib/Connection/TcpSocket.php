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
        } catch (TcpSocketException $e) {
            throw $e;
        } catch (\Exception $e) {
            // ignore it: stats logging failure shouldn't stop the whole app
        }
    }

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

        $url = 'tcp://' . $host;

        if ($persistent) {
            $socket = @pfsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        } else {
            $socket = @fsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        }

        if ($socket === false) {
            throw new TcpSocketException($host, $port, $errorMessage);
        }

        $this->socket = $socket;
    }

    /**
     * checks whether the socket connection is alive
     *
     * @return bool
     */
    protected function isConnected()
    {
        return is_resource($this->socket) && !feof($this->socket);
    }
}
