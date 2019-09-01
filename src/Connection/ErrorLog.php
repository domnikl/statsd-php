<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * a connection class that merely logs stats to
 * the PHP error log rather than to statsd
 */
class ErrorLog implements Connection
{
    public function send(string $message)
    {
        error_log($message);
    }

    public function sendMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    public function close()
    {
        // do nothing
    }
}
