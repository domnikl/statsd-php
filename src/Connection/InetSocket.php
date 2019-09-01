<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection;

abstract class InetSocket implements Connection
{
    const LINE_DELIMITER = "\n";

    const IP_HEADER_SIZE = 60;

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
     * @var float|null
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
     * @param float $timeout Connection timeout
     * @param bool $persistent (default FALSE) Use persistent connection or not
     * @param int $mtu Maximum Transmission Unit (default: 1500)
     */
    public function __construct($host = 'localhost', $port = 8125, $timeout = null, $persistent = false, $mtu = 1500)
    {
        $this->host = (string) $host;
        $this->port = (int) $port;
        $this->persistent = (bool) $persistent;
        $this->maxPayloadSize = (int) $mtu -
            self::IP_HEADER_SIZE -
            $this->getProtocolHeaderSize() -
            strlen(self::LINE_DELIMITER);

        if ($timeout === null) {
            $this->timeout = (float) ini_get('default_socket_timeout');
        } else {
            $this->timeout = (float) $timeout;
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
     * sends a message to the UDP socket
     *
     * @param string $message
     *
     * @codeCoverageIgnore
     * this is ignored because it writes to an actual socket and is not testable
     */
    public function send(string $message)
    {
        // prevent from sending empty or non-sense metrics
        if ($message === '' || !is_string($message)) {
            return;
        }

        $this->sendMessages([$message]);
    }

    /**
     * sends multiple messages to statsd
     *
     * @param array $messages
     */
    public function sendMessages(array $messages)
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
    private function cutIntoMtuSizedPackets(array $messages)
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
     * @param float|null $timeout
     * @param bool $persistent
     */
    abstract protected function connect($host, $port, $timeout, $persistent);

    /**
     * checks whether the socket connection is alive
     *
     * @return bool
     */
    abstract protected function isConnected();

    /**
     * writes a message to the socket
     *
     * @param string $message
     */
    abstract protected function writeToSocket($message);

    /**
     * @return int
     */
    abstract protected function getProtocolHeaderSize();

    /**
     * whether or not message fragmention should be allowed
     *
     * @return bool
     */
    abstract protected function allowFragmentation();
}
