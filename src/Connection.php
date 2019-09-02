<?php declare(strict_types=1);

namespace Domnikl\Statsd;

/**
 * An interface for a statsd connection implementation
 */
interface Connection
{
    /**
     * sends a message to statsd
     *
     * @param string $message
     */
    public function send(string $message): void;

    /**
     * sends multiple messages to statsd
     *
     * @param array $messages
     */
    public function sendMessages(array $messages): void;

    public function close(): void;
}
