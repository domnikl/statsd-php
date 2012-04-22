<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../../../../lib/Domnikl/Statsd/Connection.php';

/**
 * Mock object that just sets the last message in an
 * instance variable that can be checked by the test
 *
 */
class ConnectionMock extends \Domnikl\Statsd\Connection
{
    public $messages = array();

    /**
     * @param string $message
     */
    public function send($message)
    {
        $this->messages[] = $message;
    }

    /**
     * @return string
     */
    public function getLastMessage()
    {
        return $this->messages[count($this->messages) - 1];
    }
}
