<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * a connection class that merely logs stats to
 * the PHP error log rather than to statsd
 */
class ErrorLog implements Connection
{
    /**
     * Log the message
     *
     * @param string $message
     */
    public function send(string $message)
    {
        error_log($message);
    }

    /**
     * sends multiple messages to statsd
     *
     * @param array $messages
     */
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
