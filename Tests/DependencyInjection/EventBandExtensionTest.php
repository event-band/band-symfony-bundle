<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\EventBandExtension;
use EventBand\Transport\Amqp\Definition\AmqpDefinition;
use EventBand\Transport\Amqp\Definition\ExchangeDefinition;
use EventBand\Transport\Amqp\Definition\ExchangeType;
use EventBand\Transport\Amqp\Definition\QueueDefinition;
use EventBand\Transport\DelegatingTransportConfigurator;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Class EventBandExtensionTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class EventBandExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var EventBandExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles'          => array('FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle'),
            'kernel.cache_dir'        => __DIR__,
            'kernel.compiled_classes' => array(),
            'kernel.debug'            => false,
            'kernel.environment'      => 'test',
            'kernel.name'             => 'kernel',
            'kernel.root_dir'         => __DIR__,
            'kernel.charset'          => 'urf-8',
            'kernel.secret'           => 'secret'
        ]));
        $this->extension = new EventBandExtension();
        (new FrameworkExtension())->load([], $this->container);
    }

    /**
     * @test ExchangeDefinition configuration
     */
    public function amqpDefinitionExchanges()
    {
        $config = ['transports' => ['amqp' => ['connections' => ['foo' => ['exchanges' => ['foo.bar' => [
            'type' => 'headers',
            'transient' => true,
            'auto_delete' => true,
            'internal' => true,
            'bind' => [
                'bar.baz' => ['bar', 'baz']
            ]
        ]]]]]]];

        $this->loadExtension($config);

        /** @var AmqpDefinition $amqp */
        $amqp = $this->container->get(EventBandExtension::getTransportDefinitionId('amqp', 'foo'));

        $exchanges = $amqp->getExchanges();
        $this->assertCount(1, $exchanges);

        /** @var ExchangeDefinition $exchange */
        $exchange = array_shift($exchanges);
        $this->assertEquals(ExchangeType::HEADERS, $exchange->getType());
        $this->assertFalse($exchange->isDurable());
        $this->assertTrue($exchange->isAutoDeleted());
        $this->assertTrue($exchange->isInternal());
        $this->assertEquals(
            [
                'bar.baz' => ['bar', 'baz']
            ],
            $exchange->getBindings()
        );
    }

    /**
     * @test QueueDefinition configuration
     */
    public function amqpDefinitionQueues()
    {
        $config = ['transports' => ['amqp' => ['connections' => ['foo' => ['queues' => ['foo.bar' => [
            'transient' => true,
            'auto_delete' => true,
            'exclusive' => true,
            'bind' => [
                'bar.baz' => ['bar', 'baz']
            ]
        ]]]]]]];

        $this->loadExtension($config);

        /** @var AmqpDefinition $amqp */
        $amqp = $this->container->get(EventBandExtension::getTransportDefinitionId('amqp', 'foo'));

        $queues = $amqp->getQueues();
        $this->assertCount(1, $queues);

        /** @var QueueDefinition $queue */
        $queue = array_shift($queues);
        $this->assertFalse($queue->isDurable());
        $this->assertTrue($queue->isAutoDeleted());
        $this->assertTrue($queue->isExclusive());
        $this->assertEquals(
            [
                'bar.baz' => ['bar', 'baz']
            ],
            $queue->getBindings()
        );
    }

    /**
     * @test amqp registers configurator for each connection
     */
    public function amqpConfigurator()
    {
        $config = ['transports' => ['amqp' => ['connections' => ['foo' => []]]]];

        $this->loadExtension($config);

        /** @var DelegatingTransportConfigurator $configurator */
        $configurator = $this->container->get(EventBandExtension::getTransportConfiguratorId());
        $configurators = $configurator->getConfigurators();
        $this->assertCount(2, $configurators);
        $this->assertArrayHasKey('amqp.default', $configurators);
        $this->assertArrayHasKey('amqp.foo', $configurators);

        $configurator = $configurators['amqp.foo'];
        $this->assertInstanceOf('EventBand\Transport\Amqp\AmqpConfigurator', $configurator);

        $this->assertEquals(
            ['amqp' => [
                'default' => EventBandExtension::getTransportDefinitionId('amqp', 'default'),
                'foo' => EventBandExtension::getTransportDefinitionId('amqp', 'foo')
            ]],
            $this->container->getParameter('event_band.transport_definitions')
        );
    }

    private function loadExtension(array $config)
    {
        $this->extension->load([$config], $this->container);

        $this->container->getCompilerPassConfig()->setRemovingPasses(array());
        $this->container->compile();
    }
}
