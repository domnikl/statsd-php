<?php

namespace Domnikl\Statsd\Connection;

class TcpSocketException extends \RuntimeException
{
    /**
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port, \Exception $previous = null)
    {
        parent::__construct(sprintf('Couldn\'t connect to host %s:%d', $host, $port), 0, $previous);
    }
}
