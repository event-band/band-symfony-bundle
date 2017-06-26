<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DataCollector;

use CG\Proxy\MethodInvocation;
use EventBand\Bundle\DataCollector\AmqpPublicationEntry;
use EventBand\Bundle\DataCollector\PublicationInterceptor;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test for PublicationInterceptor
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class PublicationInterceptorTest extends TestCase
{
    /**
     * @var PublicationInterceptor
     */
    private $interceptor;

    /**
     * Initialize interceptor
     */
    public function setUp()
    {
        $this->interceptor = new PublicationInterceptor();
    }

    /**
     * @test matchesClass excepts AmqpDriver instances
     */
    public function classMatcher()
    {
        $driver = $this->getMock('EventBand\Transport\Amqp\Driver\AmqpDriver');
        $this->assertTrue($this->interceptor->matchesClass(new \ReflectionClass($driver)));
        $this->assertFalse($this->interceptor->matchesClass(new \ReflectionClass('ArrayObject')));
    }

    /**
     * @test matchesMethod excepts AmqpDriver::publish
     */
    public function methodMatcher()
    {
        $driver = $this->getMock('EventBand\Transport\Amqp\Driver\AmqpDriver');
        $method = new \ReflectionMethod($driver, 'publish');
        $this->assertTrue($this->interceptor->matchesMethod($method));
        $method = new \ReflectionMethod($driver, 'consume');
        $this->assertFalse($this->interceptor->matchesMethod($method));
    }

    /**
     * @test intercept publish stores publication entry and executes publish
     */
    public function interceptEntry()
    {
        $publication = $this->getMockBuilder('EventBand\Transport\Amqp\Driver\MessagePublication')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $driver = $this->getMock('EventBand\Transport\Amqp\Driver\AmqpDriver');
        $driver
            ->expects($this->once())
            ->method('publish')
            ->with($publication, 'ex', 'rk')
        ;
        $method = new \ReflectionMethod($driver, 'publish');


        $this->interceptor->intercept(new MethodInvocation(
            $method,
            $driver,
            [$publication, 'ex', 'rk'],
            []
        ));

        $entries = $this->interceptor->getLoggedPublications();
        $this->assertCount(1, $entries);
        /** @var AmqpPublicationEntry $entry */
        $entry = array_shift($entries);
        $this->assertInstanceOf('EventBand\Bundle\DataCollector\AmqpPublicationEntry', $entry);
        $this->assertEquals($publication, $entry->getPublication());
        $this->assertEquals('ex', $entry->getExchange());
        $this->assertEquals('rk', $entry->getRoutingKey());
    }
}
