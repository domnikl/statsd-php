<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * a connection class that merely logs stats to
 * the PHP error log rather than to statsd
 */
class ErrorLog implements Connection
{
    public function send(string $message): void
    {
        error_log($message);
    }

    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    public function close(): void
    {
        // do nothing
    }
}
