<?php

declare(strict_types=1);

namespace Domnikl\Test\Statsd;

use Domnikl\Statsd\Connection;

/**
 * Mock object that just sets the last message in an
 * instance variable that can be checked by the test
 */
class ConnectionMock implements Connection
{
    /**
     * @var array
     */
    public $messages = [];

    /**
     * @var bool
     */
    private $sampleAllMetrics = false;

    /**
     * @param bool $sampleAllMetrics
     */
    public function __construct($sampleAllMetrics = false)
    {
        $this->sampleAllMetrics = (bool) $sampleAllMetrics;
    }

    public function send(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return string|null
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

    public function sendMessages(array $messages): void
    {
        $this->messages[] = join("\n", $messages);
    }

    public function close(): void
    {
        // do nothing
    }
}
