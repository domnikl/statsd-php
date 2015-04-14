<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * This connection collects all messages but is not sending them. This eases 
 * feature testing when you want to assert that a specific set of messages were created. 
 */
class InMemory implements Connection
{
    private $messages = [];

    /**
     * {@inheritdoc}
     */
    public function send($message)
    {
        $this->messages[] = $message;
    }

    /** 
     * {@inheritdoc}
     */
    public function sendMessages(array $messages)
    {
        $this->messages = array_merge($this->messages = $messages);
    }

    /** 
     * Drops all messages that where collected.
     */
    public function clear()
    {
        $this->messages = [];
    }

    /**
     * Returns messages that where collected until now.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
