<?php

namespace Domnikl\Statsd\Connection;

class TcpSocketException extends \RuntimeException
{
    /**
     * @param string $host
     * @param int $port
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($host, $port, $message, \Exception $previous = null)
    {
        parent::__construct(
            sprintf('Couldn\'t connect to host "%s:%d": %s', $host, $port, $message),
            0,
            $previous
        );
    }
}
