<?php

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * connection implementation which drops all requests, useful for dev environments
 * and for disabling sending metrics entirely
 */
class Blackhole implements Connection
{
    /**
     * Drops any incoming messages
     *
     * @param string $message
     */
    public function send($message)
    {
        // do nothing
    }

    /**
     * is sampling forced?
     *
     * @return boolean
     */
    public function forceSampling()
    {
        return false;
    }
}
