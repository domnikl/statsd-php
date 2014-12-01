<?php

namespace Domnikl\Test\Statsd;

require_once __DIR__ . '/../lib/Connection.php';

/**
 * Mock object that just sets the last message in an
 * instance variable that can be checked by the test
 */
class ConnectionMock implements \Domnikl\Statsd\Connection
{
    public $messages = array();
    private $forceSampling = false;

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
		$i = count($this->messages) - 1;

		if (isset($this->messages[$i])) {
			return $this->messages[$i];
		} else {
			return null;
		}
    }

    /**
     * @param bool $bool True if sampling should be forced.
     */
    public function setForceSampling($bool)
    {
        $this->forceSampling = (bool) $bool;
    }

    /**
     * @return bool
     */
    public function forceSampling()
    {
        return $this->forceSampling;
    }
}
