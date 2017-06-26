<?php
/**
 * This file is a part of Project 3.0 verification system
 * @author Vasil coylOne Kulakov
 */

namespace EventBand\Bundle;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SubscriptionFactory implements SubscriptionFactoryInterface
{
    public function create($eventName, callable $listener, EventDispatcherInterface $eventDispatcher, $band = null)
    {
        return new ListenerServiceSubscription($eventName, $listener, $eventDispatcher, $band);
    }
}
