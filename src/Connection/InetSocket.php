<?php

declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection;

abstract class InetSocket implements Connection
{
    private const LINE_DELIMITER = "\n";

    private const IP_HEADER_SIZE = 60;

    /**
     * host name
     *
     * @var string
     */
    protected $host;

    /**
     * port number
     *
     * @var int
     */
    protected $port;

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
     * @var int
     */
    private $maxPayloadSize;

    /**
     * instantiates the Connection object and a real connection to statsd
     *
     * @param string $host Statsd hostname
     * @param int $port Statsd port
     * @param int $timeout Connection timeout
     * @param bool $persistent (default FALSE) Use persistent connection or not
     * @param int $mtu Maximum Transmission Unit (default: 1500)
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 8125,
        int $timeout = null,
        bool $persistent = false,
        int $mtu = 1500
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->persistent = $persistent;
        $this->maxPayloadSize = (int) $mtu -
            self::IP_HEADER_SIZE -
            $this->getProtocolHeaderSize() -
            strlen(self::LINE_DELIMITER);

        if ($timeout === null) {
            $this->timeout = (int) ini_get('default_socket_timeout');
        } else {
            $this->timeout = $timeout;
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * sends a message to the UDP socket
     *
     * @param string $message
     *
     * @codeCoverageIgnore
     * this is ignored because it writes to an actual socket and is not testable
     */
    public function send(string $message): void
    {
        // prevent from sending empty or non-sense metrics
        if ($message === '') {
            return;
        }

        $this->sendMessages([$message]);
    }

    /**
     * sends multiple messages to statsd
     *
     * @param array $messages
     */
    public function sendMessages(array $messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        if (!$this->isConnected()) {
            $this->connect($this->host, $this->port, $this->timeout, $this->persistent);
        }

        foreach ($this->cutIntoMtuSizedPackets($messages) as $packet) {
            $this->writeToSocket($packet);
        }
    }

    /**
     * @param array $messages
     *
     * @return array
     */
    private function cutIntoMtuSizedPackets(array $messages): array
    {
        if ($this->allowFragmentation()) {
            $message = join(self::LINE_DELIMITER, $messages) . self::LINE_DELIMITER;

            return str_split($message, $this->maxPayloadSize);
        }

        $delimiterLen = strlen(self::LINE_DELIMITER);
        $packets = [];
        $packet = '';

        foreach ($messages as $message) {
            if (strlen($packet) + strlen($message) + $delimiterLen > $this->maxPayloadSize) {
                $packets[] = $packet;
                $packet = '';
            }

            $packet .= $message . self::LINE_DELIMITER;
        }

        if (strlen($packet) > 0) {
            $packets[] = $packet;
        }

        return $packets;
    }

    /**
     * connect to the socket
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @param bool $persistent
     */
    abstract protected function connect(string $host, int $port, int $timeout, bool $persistent): void;

    /*
     * checks whether the socket connection is alive
     */
    abstract protected function isConnected(): bool;

    /**
     * writes a message to the socket
     *
     * @param string $message
     */
    abstract protected function writeToSocket(string $message): void;

    abstract protected function getProtocolHeaderSize(): int;

    abstract protected function allowFragmentation(): bool;
}
