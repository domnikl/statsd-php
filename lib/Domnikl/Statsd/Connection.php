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
     * the used socket
     *
     * @var resource
     */
    protected $_socket;

    /**
     *
     * @param string $host
     * @param int $port
     */
    public function __construct($host = 'localhost', $port = 8125)
    {
        $this->_host = (string) $host;
        $this->_port = (int) $port;
        $this->_socket = fsockopen(sprintf("udp://%s", $this->_host), $this->_port);
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
        if (0 != strlen($message) && $this->_socket) {
            fwrite($this->_socket, $message);
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
