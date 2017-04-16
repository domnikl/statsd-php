<?php

namespace Domnikl\Statsd;

/**
 * Statsd client trait
 * It allows reuse of a single Client object in several components
 */
trait ClientTrait {
    /**
     * @var null|Client
     */
    private $StatsdClient;

    /**
     * @var float
     */
    private $statsdSampleRate = 1.0;

    /**
     * @var null|string
     */
    private $statsdLocalNamespace = "";

    /**
     * @param Client      $Client
     * @param string|null $localNamespace
     * @param float|null  $sampleRate
     */
    public function setStatsdClient(Client $Client, $localNamespace = null, $sampleRate = null) {
        $this->StatsdClient = $Client;

        if ($localNamespace !== null) {
            $this->setStatsdLocalNamespace($localNamespace);
        }

        if ($sampleRate !== null) {
            $this->setStatsdSampleRate($sampleRate);
        }
    }

    /**
     * @param string $localNamespace
     */
    public function setStatsdLocalNamespace($localNamespace) {
        $this->statsdLocalNamespace = (string) $localNamespace;
    }

    /**
     * @param float $sampleRate
     */
    public function setStatsdSampleRate($sampleRate) {
        $this->statsdSampleRate = (float) $sampleRate;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function makeStatsdKey($key) {
        if ($this->statsdLocalNamespace != "") {
            return sprintf("%s.%s", $this->statsdLocalNamespace, $key);
        } else {
            return $key;
        }
    }

    /**
     * @param string $key
     */
    protected function statsdIncrement($key) {
        if ($this->StatsdClient) {
            $this->StatsdClient->increment($this->makeStatsdKey($key), $this->statsdSampleRate);
        }
    }

    /**
     * @param string $key
     */
    protected function statsdDecrement($key) {
        if ($this->StatsdClient) {
            $this->StatsdClient->decrement($this->makeStatsdKey($key), $this->statsdSampleRate);
        }
    }

    /**
     * @param string $key
     * @param int    $value
     */
    protected function statsdCount($key, $value) {
        if ($this->StatsdClient) {
            $this->StatsdClient->count($this->makeStatsdKey($key), $value, $this->statsdSampleRate);
        }
    }

    /**
     * @param string $key
     * @param int    $value the timing in ms
     */
    protected function statsdTiming($key, $value) {
        if ($this->StatsdClient) {
            $this->StatsdClient->timing($this->makeStatsdKey($key), $value, $this->statsdSampleRate);
        }
    }

    /**
     * @param string     $key
     * @param string|int $value
     */
    protected function statsdGauge($key, $value) {
        if ($this->StatsdClient) {
            $this->StatsdClient->gauge($this->makeStatsdKey($key), $value);
        }
    }

    /**
     * @param string $key
     * @param int    $value
     */
    protected function statsdSet($key, $value) {
        if ($this->StatsdClient) {
            $this->StatsdClient->set($this->makeStatsdKey($key), $value);
        }
    }
}
