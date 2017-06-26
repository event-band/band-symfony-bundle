<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DataCollector;

use EventBand\Bundle\DataCollector\AmqpDataCollector;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test for AmqpDataCollector
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AmqpDataCollectorTest extends TestCase
{
    /**
     * @var AmqpDataCollector
     */
    private $collector;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $tracer;

    /**
     * Initialize collector with tracer mock
     */
    protected function setUp()
    {
        $this->tracer = $this->getMock('EventBand\Bundle\DataCollector\PublicationTracer');
        $this->collector = new AmqpDataCollector($this->tracer);
    }

    /**
     * @test collect stores publications
     */
    public function collectPublications()
    {
        $this->tracer
            ->expects($this->once())
            ->method('getLoggedPublications')
            ->will($this->returnValue([1,2,3]))
        ;

        $this->collector->collect(new Request(), new Response());

        $this->assertEquals([1,2,3], $this->collector->getPublications());
        $this->assertEquals(3, $this->collector->getPublicationCount());
    }
}
