<?php
namespace EventBand\Bundle;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface SubscriptionFactoryInterface
{
    public function create($eventName, callable $listener, EventDispatcherInterface $eventDispatcher, $band = null);
}
