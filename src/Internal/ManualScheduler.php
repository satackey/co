<?php

namespace mpyw\Co\Internal;
use mpyw\Co\CURLException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class ManualScheduler extends AbstractScheduler
{
    /**
     * cURL handles those have not been dispatched.
     * @var array
     */
    private $queue = [];

    /**
     * Constructor.
     * Initialize cURL multi handle.
     * @param CoOption                  $options
     * @param resource|\CurlMultiHandle $mh      curl_multi
     */
    public function __construct(CoOption $options, $mh)
    {
        $this->mh = $mh;
        $this->options = $options;
    }

    /**
     * Call curl_multi_add_handle() or push into queue.
     * @param resource|\CurlHandle $ch
     * @return PromiseInterface
     */
    public function add($ch)
    {
        $deferred = new Deferred;
        $this->options['concurrency'] > 0
        && count($this->added) >= $this->options['concurrency']
            ? $this->addReserved($ch, $deferred)
            : $this->addImmediate($ch, $deferred);
        return $deferred->promise();
    }

    /**
     * Are there no cURL handles?
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->added && !$this->queue;
    }

    /**
     * Call curl_multi_add_handle().
     * @param resource|\CurlHandle $ch
     * @param Deferred             $deferred
     */
    private function addImmediate($ch, Deferred $deferred = null)
    {
        $errno = curl_multi_add_handle($this->mh, $ch);
        if ($errno !== CURLM_OK) {
            // @codeCoverageIgnoreStart
            $msg = curl_multi_strerror($errno) . ': ' . TypeUtils::getIdOfCurlHandleOrGenerator($ch);
            $deferred && $deferred->reject(new \RuntimeException($msg));
            return;
            // @codeCoverageIgnoreEnd
        }
        $this->added[TypeUtils::getIdOfCurlHandleOrGenerator($ch)] = $ch;
        $deferred && $this->deferreds[TypeUtils::getIdOfCurlHandleOrGenerator($ch)] = $deferred;
    }

    /**
     * Push into queue.
     * @param resource|\CurlHandle $ch
     * @param Deferred $deferred
     */
    private function addReserved($ch, Deferred $deferred = null)
    {
        $this->queue[TypeUtils::getIdOfCurlHandleOrGenerator($ch)] = $ch;
        $deferred && $this->deferreds[TypeUtils::getIdOfCurlHandleOrGenerator($ch)] = $deferred;
    }

    /**
     * Add cURL handles from waiting queue.
     */
    protected function interruptConsume()
    {
        if ($this->queue) {
            $this->addImmediate(array_shift($this->queue));
        }
    }
}
