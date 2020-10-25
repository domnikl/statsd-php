<?php

declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection;

class File implements Connection
{
    /**
     * @var null|resource|closed-resource
     */
    private $handle;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $mode;

    public function __construct(string $filePath, string $mode = "a+")
    {
        $this->filePath = $filePath;
        $this->mode = $mode;
    }

    private function open(): void
    {
        $this->handle = @fopen($this->filePath, $this->mode);
    }

    public function send(string $message): void
    {
        // prevent from sending empty or non-sense metrics
        if ($message === '') {
            return;
        }

        if (!$this->handle) {
            $this->open();
        }

        if ($this->handle) {
            fwrite($this->handle, $message . PHP_EOL);
        }
    }

    public function sendMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    public function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;
    }
}
