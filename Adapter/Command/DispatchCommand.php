<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony\Command;

use EventBand\Transport\EventConsumer;

/**
 * Class DispatchCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class DispatchCommand extends AbstractDispatchCommand
{
    private $dispatcher;
    private $consumers;
    private $bandName;
    private $defaultTimeout;

    public function __construct($bandName = null, $defaultTimeout = 0)
    {
        $this->bandName = $bandName;
        $this->defaultTimeout = $defaultTimeout;

        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('dispatch');
    }

    public function getBandName()
    {
        return $this->bandName;
    }

    public function getDefaultTimeout()
    {
        return $this->defaultTimeout;
    }

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function getDispatcher()
    {
        if (!$this->dispatcher) {
            throw new \BadMethodCallException('No dispatcher was set');
        }

        return $this->dispatcher;
    }

    public function setConsumer(EventConsumer $consumer, $band)
    {
        $this->consumers[$band] = $consumer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsumer($band)
    {
        if (!isset($this->consumers[$band])) {
            throw new \OutOfBoundsException(sprintf('No consumer was set for band %s', $band));
        }

        return $this->consumers[$band];
    }
}