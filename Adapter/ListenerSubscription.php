<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony;

use EventBand\AbstractSubscription;
use EventBand\BandDispatcher;
use EventBand\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ListenerSubscription extends AbstractSubscription
{
    private $listener;
    private $eventDispatcher;

    public function __construct($eventName, $listener, EventDispatcherInterface $eventDispatcher, $band = null)
    {
        $this->listener = $listener;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($eventName, $band);
    }

    public function getListener()
    {
        return $this->listener;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(Event $event, BandDispatcher $dispatcher)
    {
//        if (method_exists($event, 'getName'))
        $symfonyEvent = $event instanceof SymfonyEvent ? $event : new SymfonyEventWrapper($event);

        call_user_func($this->listener, $symfonyEvent, $this->getEventName(), $this->getEventDispatcher());

        return !$symfonyEvent->isPropagationStopped();
    }
}