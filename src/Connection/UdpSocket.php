<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service in UDP mode (standard)
 *
 * @codeCoverageIgnore
 */
class UdpSocket extends InetSocket implements Connection
{
    const HEADER_SIZE = 8;

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
    public function send(string $message)
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

        // sleeping for 10 millionths of a second dramatically improves UDP reliability
        usleep(10);
    }

    /**
     * @param string $host
     * @param int $port
     * @param float|null $timeout
     * @param bool $persistent
     */
    protected function connect($host, $port, $timeout, $persistent = false)
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = 'udp://' . $host;

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

    public function close()
    {
        fclose($this->socket);

        $this->socket = null;
        $this->isConnected = false;
    }

    /**
     * @return int
     */
    protected function getProtocolHeaderSize()
    {
        return self::HEADER_SIZE;
    }

    /**
     * message fragmention should not be allowed on UDP because packets
     * regularly arrive out-of-order, and if they are not split evenly on a
     * line delimiter, they will be combined in strange ways.
     *
     * @return bool
     */
    protected function allowFragmentation()
    {
        return false;
    }
}
