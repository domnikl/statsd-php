<?php declare(strict_types=1);

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

    /**
     * {@inheritdoc}
     */
    public function send(string $message)
    {
        $this->messages[] = $message;
    }

    /** 
     * {@inheritdoc}
     */
    public function sendMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    /** 
     * Drops all messages that were collected.
     */
    public function clear()
    {
        $this->messages = [];
    }

    /**
     * Returns messages that were collected until now.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function close()
    {
        $this->clear();
    }
}
