<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service
 *
 * @author Dominik Liebler <liebler.dominik@googlemail.com>
 */
class Socket
	implements Connection
{
    /**
     * host name
     *
     * @var string
     */
    protected $_host;

    /**
     * port number
     *
     * @var int
     */
    protected $_port;

    /**
     * the used socket resource
     *
     * @var resource
     */
    protected $_socket;

    /**
     * is sampling allowed?
     *
     * @var bool
     */
    protected $_forceSampling = false;

    /**
     * instantiates the Connection object and a real connection to statsd
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

    /**
     * is sampling forced?
     *
     * @return boolean
     */
    public function forceSampling()
    {
        return (bool) $this->_forceSampling;
    }
}
