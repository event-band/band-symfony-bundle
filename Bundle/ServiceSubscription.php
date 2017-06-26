<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle;

use EventBand\AbstractSubscription;
use EventBand\BandDispatcher;
use EventBand\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ServiceSubscription
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ServiceSubscription extends AbstractSubscription
{
    private $container;
    private $serviceId;
    private $method;

    public function __construct($eventName, ContainerInterface $container, $serviceId, $method = null, $band = '')
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
        $this->method = $method;

        parent::__construct($eventName, $band);
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(Event $event, BandDispatcher $dispatcher)
    {
        $service = $this->container->get($this->serviceId);
        $callback = $this->method ? [$service, $this->method] : $service;

        if (!is_callable($callback)) {
            throw new \RuntimeException(
                sprintf('Wrong service callback [id: "%s", method: "%s"]', $this->serviceId, $this->method)
            );
        }

        return $callback($event, $dispatcher);
    }
}