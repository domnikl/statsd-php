<?php

namespace Domnikl\Statsd;

/**
 * An interface for a Statsd connection implementation
 */
interface Connection
{
    /**
     * sends a message to Statsd
     *
     * @param string $message
     */
    public function send($message);

    /**
     * is sampling forced?
     *
     * @return boolean
     */
    public function forceSampling();
}
