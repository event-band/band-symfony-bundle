<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony\Tests;

use EventBand\Adapter\Symfony\BandEventDispatcher;
use EventBand\Adapter\Symfony\ListenerSubscription;
use EventBand\Adapter\Symfony\SymfonyEventWrapper;
use EventBand\Subscription;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class BandEventDispatcherTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class BandEventDispatcherTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;
    /**
     * @var BandEventDispatcher
     */
    private $bandDispatcher;

    protected function setUp()
    {
        $eventDispatcherInterface = 'Symfony\Component\EventDispatcher\EventDispatcherInterface';
        $this->eventDispatcher = $this->getMock($eventDispatcherInterface, array_merge(get_class_methods($eventDispatcherInterface), ['additionalMethod']));
        $this->bandDispatcher = new BandEventDispatcher($this->eventDispatcher, '~prefix~');
    }

    /**
     * @test band dispatch will delegate dispatching to internal event dispatcher
     */
    public function dispatchEventWithInternal()
    {
        $event = $this->getMock('EventBand\Event');
        $event
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('event.name'))
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('event.name', $this->callback(function (SymfonyEventWrapper $wrapper) use ($event) {
                return $event === $wrapper->getWrappedEvent();
            }))
        ;

        $this->assertTrue($this->bandDispatcher->dispatchEvent($event));
    }

    /**
     * @test band dispatch will prefix event
     */
    public function dispatchEventWithBand()
    {
        $event = $this->getMock('EventBand\Event');
        $event
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('event.name'))
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('~prefix~.band_name.event.name', $this->anything())
        ;

        $this->bandDispatcher->dispatchEvent($event, 'band_name');
    }

    /**
     * @test if propagation was stopped dispatch will return false
     */
    public function dispatchEventPropagation()
    {
        $event = $this->getMock('EventBand\Event');
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function ($eventName, SymfonyEventWrapper $wrapper) {
                $wrapper->stopPropagation();
            }))
        ;

        $this->assertFalse($this->bandDispatcher->dispatchEvent($event));
    }

    /**
     * @test subscribe add new adapter listener
     */
    public function addAdapterListener()
    {
        $subscription = $this->getMock('EventBand\Subscription');
        $subscription
            ->expects($this->any())
            ->method('getEventName')
            ->will($this->returnValue('event.name'))
        ;
        $subscription
            ->expects($this->any())
            ->method('getBand')
            ->will($this->returnValue('band_name'))
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('addListener')
            ->with(
                '~prefix~.band_name.event.name',
                $this->isInstanceOf('EventBand\Adapter\Symfony\AdapterEventListener'),
                10
            );

        $this->bandDispatcher->subscribe($subscription, 10);
    }

    /**
     * @test unsubscribe remove related adapter listener
     */
    public function removeWrapperListener()
    {
        $subscription = $this->getMock('EventBand\Subscription');
        $subscription
            ->expects($this->any())
            ->method('getEventName')
            ->will($this->returnValue('event.name'))
        ;
        $subscription
            ->expects($this->any())
            ->method('getBand')
            ->will($this->returnValue('band_name'))
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('removeListener')
            ->with(
                '~prefix~.band_name.event.name',
                $this->isInstanceOf('EventBand\Adapter\Symfony\AdapterEventListener')
            );

        $this->bandDispatcher->subscribe($subscription, 10);
        $this->bandDispatcher->unsubscribe($subscription);
    }

    /**
     * @test add/removeListener calls internal dispatcher
     */
    public function addRemoveListener()
    {
        $callback = 'var_dump';

        $this->eventDispatcher
            ->expects($this->once())
            ->method('addListener')
            ->with('event.name', $callback, 1024)
        ;

        $this->bandDispatcher->addListener('event.name', $callback, 1024);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('removeListener')
            ->with('event.name', $callback)
        ;

        $this->bandDispatcher->removeListener('event.name', $callback);
    }

    /**
     * @test if removeListener called without addListener through same object it should still call internal remove
     */
    public function removeNotSubscribedListener()
    {
        $callback = 'var_dump';

        $this->eventDispatcher
            ->expects($this->once())
            ->method('removeListener')
            ->with('event.name', $callback)
        ;

        $this->bandDispatcher->removeListener('event.name', $callback);
    }

    /**
     * @test getSubscriptions returns iterator with subscriptions
     */
    public function subscriptionIterator()
    {
        $subscription1 = $this->getMock('EventBand\Subscription');
        $subscription2 = $this->getMock('EventBand\Subscription');

        $this->bandDispatcher->subscribe($subscription1);
        $this->bandDispatcher->subscribe($subscription2);

        $subscriptions = $this->bandDispatcher->getSubscriptions();
        $this->assertInstanceOf('Iterator', $subscriptions);
        $this->assertCount(2, $subscriptions);

        $this->assertContains($subscription1, $subscriptions);
        $this->assertContains($subscription2, $subscriptions);
    }

    /**
     * @test addListener adds subscription and removeListener removes it
     */
    public function listenerSubscription()
    {
        $listener = function (SymfonyEvent $event) {};
        $this->bandDispatcher->addListener('event.name', $listener, 100);

        $subscriptions = $this->bandDispatcher->getSubscriptions();
        $subscriptions = iterator_to_array($subscriptions);

        $this->assertCount(1, $subscriptions);

        /** @var ListenerSubscription $subscription */
        $subscription = current($subscriptions);
        $this->assertInstanceOf('EventBand\Adapter\Symfony\ListenerSubscription', $subscription);
        $this->assertEquals('event.name', $subscription->getEventName());
        $this->assertNull($subscription->getBand());
        $this->assertSame($listener, $subscription->getListener());
        $this->assertSame($this->eventDispatcher, $subscription->getEventDispatcher());

        $this->eventDispatcher
            ->expects($this->once())
            ->method('removeListener')
            ->with('event.name', $listener)
        ;

        $this->bandDispatcher->removeListener('event.name', $listener);

        $this->assertCount(0, $this->bandDispatcher->getSubscriptions());
    }

    /**
     * @test getSubscriptionPriority returns priority
     */
    public function subscriptionPriority()
    {
        $subscription = $this->getMock('EventBand\Subscription');
        $this->bandDispatcher->subscribe($subscription, 666);

        $this->assertEquals(666, $this->bandDispatcher->getSubscriptionPriority($subscription));
    }

    /**
     * @test getSubscriptionPriority throws and exception
     * @expectedException OutOfBoundsException
     */
    public function unknownSubscriptionPriority()
    {
        $this->bandDispatcher->getSubscriptionPriority($this->getMock('EventBand\Subscription'));
    }

    /**
     * @test hasListener proxy to internal dispatcher
     */
    public function proxyHasListener()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->with('event.name')
            ->will($this->returnValue(true))
        ;

        $this->assertTrue($this->bandDispatcher->hasListeners('event.name'));
    }

    /**
     * @test undefined methods are proxied to eventDispatcher with __call()
     */
    public function proxyMethods()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('additionalMethod')
            ->with(1, 2, 3)
            ->will($this->returnValue('result'))
        ;

        $this->assertEquals('result', $this->bandDispatcher->additionalMethod(1, 2, 3));
    }

    /**
     * @test if no such method exists in eventDispatcher, exception it thrown
     * @expectedException \BadMethodCallException
     */
    public function undefinedProxyMethod()
    {
        $this->bandDispatcher->undefinedMethod();
    }
}
