<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony;

use EventBand\BandDispatcher;
use EventBand\Event;
use EventBand\Subscription;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class AdapterListener
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AdapterEventListener
{
    private $dispatcher;
    private $subscription;

    public function __construct(BandDispatcher $dispatcher, Subscription $subscription)
    {
        $this->dispatcher = $dispatcher;
        $this->subscription = $subscription;
    }

    public function __invoke(SymfonyEvent $symfonyEvent)
    {
        if ($symfonyEvent instanceof Event) {
            $event = $symfonyEvent;
        } elseif ($symfonyEvent instanceof SymfonyEventWrapper) {
            $event = $symfonyEvent->getWrappedEvent();
        } else {
            $event = new EventWrapper($symfonyEvent, $this->subscription->getEventName());
        }

        if (!$this->subscription->dispatch($event, $this->dispatcher)) {
            $symfonyEvent->stopPropagation();
        }
    }
}
