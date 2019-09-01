<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection;

class File implements Connection
{
    /**
     * @var resource|null
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

    /**
     * @param string $filePath
     * @param string $mode
     */
    public function __construct($filePath, $mode = "a+")
    {
        $this->filePath = $filePath;
        $this->mode = $mode;
    }

    private function open()
    {
        $this->handle = @fopen($this->filePath, $this->mode);
    }

    /**
     * @inheritdoc
     */
    public function send(string $message)
    {
        // prevent from sending empty or non-sense metrics
        if ($message === '' || !is_string($message)) {
            return;
        }

        if (!$this->handle) {
            $this->open();
        }

        if ($this->handle) {
            fwrite($this->handle, $message . PHP_EOL);
        }
    }

    /**
     * @inheritdoc
     */
    public function sendMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    public function close()
    {
        @fclose($this->handle);

        $this->handle = null;
    }
}
