<?php

declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * This connection collects all messages but is not sending them. This eases
 * feature testing when you want to assert that a specific set of messages were created.
 */
class InMemory implements Connection
{
    /**
     * @var string[]
     */
    private $messages = [];

    public function send(string $message): void
    {
        $this->messages[] = $message;
    }

    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    /**
     * Drops all messages that were collected.
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Returns messages that were collected until now.
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function close(): void
    {
        $this->clear();
    }
}
