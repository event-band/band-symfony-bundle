<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony;

use EventBand\BandDispatcher;
use EventBand\Event;
use EventBand\Subscription;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * BandDispatcher implementation based on symfony EventDispatcher
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class BandEventDispatcher implements EventDispatcherInterface, BandDispatcher
{
    const DEFAULT_BAND_PREFIX = '__event_band__';

    private $dispatcher;
    private $bandPrefix;
    private $subscriptions;

    public function __construct(EventDispatcherInterface $dispatcher, $bandPrefix = self::DEFAULT_BAND_PREFIX)
    {
        $this->dispatcher = $dispatcher;
        $this->bandPrefix = $bandPrefix;
        $this->subscriptions = new \SplObjectStorage();
    }

    /**
     * {@inheritDoc}
     */
    public function dispatchEvent(Event $event, $band = null)
    {
        $eventName = $this->getBandEventName($event->getName(), $band);

        $symfonyEvent = $event instanceof SymfonyEvent ? $event : new SymfonyEventWrapper($event);
        $this->dispatcher->dispatch($eventName, $symfonyEvent);

        return !$symfonyEvent->isPropagationStopped();
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($eventName, SymfonyEvent $event = null)
    {
        if ($event === null) {
            $event = new SerializableSymfonyEvent();
        }
        if (method_exists($event, 'setName')) {
            $event->setName($eventName);
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(Subscription $subscription, $priority = 0)
    {
        $this->attachListener($subscription, new AdapterEventListener($this, $subscription), $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->attachListener(new ListenerSubscription($eventName, $listener, $this->dispatcher), $listener, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        // Copy-Pasted from EventDispatcher::addSubscriber() to ensure addListener() call
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    protected function attachListener(Subscription $subscription, $listener, $priority)
    {
        $this->subscriptions->attach($subscription, [$listener, $priority]);
        $this->dispatcher->addListener($this->getSubscriptionEventName($subscription), $listener, $priority);
    }

    /**
     * @param Subscription $subscription
     */
    public function unsubscribe(Subscription $subscription)
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->dispatcher->removeListener($this->getSubscriptionEventName($subscription),$this->subscriptions->offsetGet($subscription)[0]);
            $this->subscriptions->detach($subscription);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeListener($eventName, $listener)
    {
        if ($subscription = $this->findListenerSubscription($listener)) {
            $this->unsubscribe($subscription);
        } else {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        // Copy-Pasted from EventDispatcher::removeSubscriber() to ensure removeListener() call
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($eventName, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }

    /**
     * @param callable $listener
     *
     * @return Subscription|null
     */
    protected function findListenerSubscription($listener)
    {
        foreach ($this->subscriptions as $subscription) {
            if ($this->subscriptions->getInfo()[0] === $listener) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscriptions()
    {
        // TODO: sync with internal
        return new \ArrayIterator(iterator_to_array($this->subscriptions));
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners($eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscriptionPriority(Subscription $subscription)
    {
        // TODO: sync with internal
        if (!$this->subscriptions->contains($subscription)) {
            throw new \OutOfBoundsException('Subscription does not exists');
        }

        return $this->subscriptions->offsetGet($subscription)[1];
    }

    /**
     * {@inheritdoc}
     */
    public function getListenerPriority($eventName, $listener)
    {
        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    /**
     * {@inheritDoc}
     */
    public function hasListeners($eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Proxy undefined methods to internal dispatcher
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (!is_callable([$this->dispatcher, $name])) {
            throw new \BadMethodCallException(sprintf('EventDispatcher does not have a "%s" method', $name));
        }

        return call_user_func_array([$this->dispatcher, $name], $arguments);
    }

    private function getBandEventName($eventName, $band)
    {
        if (!empty($band)) {
            $eventName = sprintf('%s.%s.%s', $this->bandPrefix, $band, $eventName);
        }

        return $eventName;
    }

    private function getSubscriptionEventName(Subscription $subscription)
    {
        return $this->getBandEventName($subscription->getEventName(), $subscription->getBand());
    }
}
