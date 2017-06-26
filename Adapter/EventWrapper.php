<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony;

use EventBand\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class EventWrapper
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class EventWrapper implements Event
{
    /**
     * Name of the event. Used for async events dispatching.
     * Since the name property of symfony events is deprecated, we moved it here.
     * It will not break any symfony concept, because will be set under the hood of symfony adapter.
     * No need to set it from application logic
     *
     * @var string
     */
    protected $name;

    /**
     * @var SymfonyEvent
     */
    private $symfonyEvent;

    /**
     * Wrap symfony event
     *
     * @param SymfonyEvent  $symfonyEvent
     * @param string|null   $name
     */
    public function __construct(SymfonyEvent $symfonyEvent, $name = null)
    {
        $this->symfonyEvent = $symfonyEvent;
        $this->name = $name;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name !== null ? $this->name : $this->symfonyEvent->getName();
    }
}
