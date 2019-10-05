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
     * @var resource|null
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
    public function send(string $message): void
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
    protected function writeToSocket(string $message): void
    {
        if ($this->socket === null) {
            return;
        }

        // suppress all errors
        @fwrite($this->socket, $message);

        // sleeping for 10 millionths of a second dramatically improves UDP reliability
        usleep(10);
    }

    /**
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param bool $persistent
     */
    protected function connect(string $host, int $port, int $timeout, bool $persistent = false): void
    {
        $errorNumber = 0;
        $errorMessage = '';

        $url = 'udp://' . $host;

        if ($persistent) {
            $this->socket = @pfsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        } else {
            $this->socket = @fsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        }
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
    protected function isConnected(): bool
    {
        return $this->socket !== null && feof($this->socket) === false;
    }

    public function close(): void
    {
        if ($this->socket !== null) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    protected function getProtocolHeaderSize(): int
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
    protected function allowFragmentation(): bool
    {
        return false;
    }
}
