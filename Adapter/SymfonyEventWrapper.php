<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony;

use EventBand\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class EventWrapper
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SymfonyEventWrapper extends SymfonyEvent
{
    private $wrappedEvent;

    public function __construct(Event $internalEvent)
    {
        $this->wrappedEvent = $internalEvent;
    }

    public function getWrappedEvent()
    {
        return $this->wrappedEvent;
    }
}