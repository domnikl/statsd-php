<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection;

abstract class InetSocket implements Connection
{
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
     * Maximum Transmission Unit
     *
     * http://en.wikipedia.org/wiki/Maximum_transmission_unit
     *
     * @var int
     */
    private $mtu;

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
        $this->mtu = (int) $mtu;
        $this->persistent = (bool) $persistent;

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
    public function send($message)
    {
        // prevent from sending empty or non-sense metrics
        if ($message === '' || !is_string($message)) {
            return;
        }

        if (!$this->isConnected()) {
            $this->connect($this->host, $this->port, $this->timeout, $this->persistent);
        }

        $this->writeToSocket($message);
    }

    /**
     * sends multiple messages to statsd
     *
     * @param array $messages
     */
    public function sendMessages(array $messages)
    {
        $message = join("\n", $messages);

        if (strlen($message) > $this->mtu) {
            $messageBatches = $this->cutIntoMtuSizedMessages($messages);

            foreach ($messageBatches as $messageBatch) {
                $this->send(join("\n", $messageBatch));
            }
        } else {
            $this->send($message);
        }
    }

    /**
     * @param array $messages
     *
     * @return array
     */
    private function cutIntoMtuSizedMessages(array $messages)
    {
        $index = 0;
        $sizedMessages = [];
        $packageLength = 0;

        foreach ($messages as $message) {
            $messageLength = strlen($message);

            if ($messageLength + $packageLength > $this->mtu) {
                ++$index;
                $packageLength = 0;
            }

            $sizedMessages[$index][] = $message;
            $packageLength += $messageLength;
        }

        return $sizedMessages;
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
}
