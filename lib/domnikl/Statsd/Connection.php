<?php

namespace Domnikl\Statsd;

/**
 *
 */
class Connection
{
    /**
     * @var string
     */
    protected $_host;

    /**
     * @var int
     */
    protected $_port;

    /**
     *
     * @param string $host
     * @param int $port
     */
    public function __construct($host = 'localhost', $port = 8125)
    {
        $this->_host = (string) $host;
        $this->_port = (int) $port;
    }

    /**
     * sends a message to the UDP socket
     *
     * @param $message
     *
     * @return void
     */
    public function send($message)
    {
        if (0 != strlen($message)) {
            $socket = fsockopen(sprintf("udp://%s", $this->_host), $this->_port);

            if ($socket) {
                fwrite($socket, $message);
                fclose($socket);
            }
        }
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }


    /**
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }
}
