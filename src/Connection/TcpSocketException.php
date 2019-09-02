<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

class TcpSocketException extends \RuntimeException
{
    public function __construct(string $host, int $port, string $message, \Exception $previous = null)
    {
        parent::__construct(
            sprintf('Couldn\'t connect to host "%s:%d": %s', $host, $port, $message),
            0,
            $previous
        );
    }
}
