<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony\Tests;

use EventBand\Adapter\Symfony\EventWrapper;
use EventBand\Adapter\Symfony\SymfonyEventWrapper;
use EventBand\Adapter\Symfony\AdapterEventListener;
use EventBand\Event;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class WrapperListenerTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AdapterEventListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\EventBand\BandDispatcher
     */
    private $dispatcher;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\EventBand\Subscription
     */
    private $subscription;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\EventBand\Event
     */
    private $event;
    /**
     * @var AdapterEventListener
     */
    private $listener;

    protected function setUp()
    {
        $this->dispatcher = $this->getMock('EventBand\BandDispatcher');
        $this->subscription = $this->getMock('EventBand\Subscription');
        $this->event = $this->getMock('EventBand\Event');
        $this->listener = new AdapterEventListener($this->dispatcher, $this->subscription);
    }

    /**
     * @test listener executes dispatch
     */
    public function subscriptionDispatch()
    {
        $this->subscription
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->event, $this->dispatcher)
        ;


        call_user_func($this->listener, new SymfonyEventWrapper($this->event));
    }

    /**
     * @test if subscription return false propagation should be stopped
     */
    public function propagation()
    {
        $this->subscription
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->event, $this->dispatcher)
            ->will($this->returnValue(true))
        ;
        $this->subscription
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->event, $this->dispatcher)
            ->will($this->returnValue(false))
        ;

        $wrapper = new SymfonyEventWrapper($this->event);

        call_user_func($this->listener, $wrapper);
        $this->assertFalse($wrapper->isPropagationStopped());

        call_user_func($this->listener, $wrapper);
        $this->assertTrue($wrapper->isPropagationStopped());
    }

    /**
     * @test event name is passed with wrapped event
     */
    public function symfonyEventName()
    {
        $symfonyEvent = $this->getMock('Symfony\Component\EventDispatcher\Event');

        $eventName = 'event_name';
        $this->subscription
            ->expects($this->any())
            ->method('getEventName')
            ->will($this->returnValue($eventName));

        $this->subscription
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (Event $e) use ($eventName) {
                return $e->getName() == $eventName;
            }))
            ->will($this->returnValue(true))
        ;

        call_user_func($this->listener, $symfonyEvent);
    }
}
