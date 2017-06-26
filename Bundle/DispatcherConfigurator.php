<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle;

use EventBand\Adapter\Symfony\ListenerSubscription;
use EventBand\BandDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DispatcherConfigurator
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class DispatcherConfigurator
{
    private $container;
    private $eventDispatcher;
    private $listeners = [];
    /**
     * @var SubscriptionFactoryInterface
     */
    private $factory;

    public function __construct(
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher,
        SubscriptionFactoryInterface $factory
    )
    {
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
        $this->factory = $factory;
    }

    public function addListenerService($id, $event, $method = null, $band = null, $priority = 0)
    {
        $this->listeners[(string) $band][$event][] = [$id, $method, $priority];
    }

    public function addSubscriberService($class, $serviceId, $band = null)
    {
        foreach ($class::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListenerService($serviceId, $eventName, $params, $band, 0);
            } elseif (is_string($params[0])) {
                $this->addListenerService($serviceId, $eventName, $params[0], $band, isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListenerService($serviceId, $eventName, $listener[0], $band, isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    public function configure(BandDispatcher $dispatcher)
    {
        foreach ($this->listeners as $band => $events) {
            foreach ($events as $event => $definitions) {
                foreach ($definitions as $definition) {
                    $subscription = $this->factory->create(
                        $event,
                        function (Event $event, $eventName, EventDispatcherInterface $eventDispatcher) use ($definition) {
                            return call_user_func([$this->container->get($definition[0]), $definition[1]], $event, $eventName, $eventDispatcher);
                        },
                        $this->eventDispatcher,
                        $band
                    );
                    $dispatcher->subscribe($subscription, $definition[2]);
                }
            }
        }
    }
}
