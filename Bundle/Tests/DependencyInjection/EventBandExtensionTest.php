<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\EventBandExtension;
use EventBand\Transport\Amqp\Definition\AmqpDefinition;
use EventBand\Transport\Amqp\Definition\ExchangeDefinition;
use EventBand\Transport\Amqp\Definition\ExchangeType;
use EventBand\Transport\Amqp\Definition\QueueDefinition;
use EventBand\Transport\DelegatingTransportConfigurator;
use JMS\SerializerBundle\DependencyInjection\JMSSerializerExtension;
use JMS\SerializerBundle\JMSSerializerBundle;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\Config\Resource\FileResource;
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
            'kernel.cache_dir'        => sys_get_temp_dir(),
            'kernel.compiled_classes' => [],
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

    /**
     * @test serialize amqp converter
     */
    public function amqpSerializeConverters()
    {
        $this->loadExtension(
            [
                'transports' => [
                    'amqp' => [
                        'converters' => [
                            'default' => [
                                'serialize' => [
                                    'serializer' => 'ser1'
                                ]
                            ]
                        ]
                    ]
                ],
                'serializers' => [
                    'ser1' => ['native' => []]
                ]
            ],
            false
        );

        $id = EventBandExtension::getAmqpConverterId('default');
        $this->assertTrue($this->container->hasDefinition($id));
        $this->container->getDefinition($id)->setPublic(true);
        $this->container->compile();

        $converter = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Transport\Amqp\Driver\MessageSerializeConverter', $converter);
        $this->assertEquals(EventBandExtension::getSerializerId('ser1'), $this->container->getDefinition($id)->getArgument(0)->__toString());
    }

    /**
     * @test native serializer configuration
     */
    public function nativeSerializers()
    {
        $this->loadExtension(
            [
                'serializers' => [
                    'default' => [
                        'native' => []
                    ]
                ]
            ],
            false
        );

        $id = EventBandExtension::getSerializerId('default');
        $this->assertTrue($this->container->hasDefinition($id));
        $this->container->getDefinition($id)->setPublic(true);
        $this->container->compile();

        $serializer = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Serializer\NativeEventSerializer', $serializer);

        // Test no serializer configs are loaded
        $resources = $this->container->getResources();
        foreach ($resources as $resource) {
            if (!$resource instanceof FileResource){
                continue;
            }
            $this->assertNotContains('EventBand/SymfonyBundle/Resources/config/serializer/', $resource->getResource());
        }
    }

    /**
     * @test jms serializer configuration
     */
    public function jmsSerializers()
    {
        $this->loadExtension(
            [
                'serializers' => [
                    'default' => [
                        'jms' => [
                            'format' => 'xml'
                        ]
                    ]
                ]
            ],
            false
        );
        $jmsBundle = new JMSSerializerBundle();
        $jmsBundle->getContainerExtension()->load([], $this->container);
        $jmsBundle->build($this->container);

        $id = EventBandExtension::getSerializerId('default');
        $this->assertTrue($this->container->hasDefinition($id));
        $this->container->getDefinition($id)->setPublic(true);
        $this->container->compile();

        $serializer = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Serializer\Jms\JmsEventContainerSerializer', $serializer);
        $this->assertEquals('xml', $this->container->getDefinition($id)->getArgument(1));
    }

    /**
     * @test pattern router configuration
     */
    public function patternRouters()
    {
        $this->loadExtension(
            [
                'routers' => [
                    'default' => [
                        'pattern' => '{param1}{param2}'
                    ]
                ]
            ],
            false
        );

        $id = EventBandExtension::getRouterId('default');
        $this->assertTrue($this->container->hasDefinition($id));
        $this->container->getDefinition($id)->setPublic(true);
        $this->container->compile();

        $router = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Routing\EventPatternRouter', $router);
        $this->assertEquals('{param1}{param2}', $router->getPattern());
    }

    /**
     * @test amqp publisher parameters
     */
    public function amqpPublishers()
    {
        $this->loadExtension(
            [
                'transports' => [
                    'amqp' => [
                        'connections' => [
                            'con1' => []
                        ],
                        'converters' => [
                            'foo' => [
                                'serialize' => []
                            ]
                        ]
                    ]
                ],
                'routers' => [
                    'router1' => [
                        'pattern' => '{param}'
                    ]
                ],
                'publishers' => [
                    'event' => [
                        'events' => ['event.test'],
                        'transport' => [
                            'amqp' => [
                                'connection' => 'con1',
                                'converter' => 'foo',
                                'exchange' => 'test',
                                'router' => 'router1',
                                'persistent' => false,
                                'mandatory' => true,
                                'immediate' => true
                            ]
                        ]
                    ]
                ]
            ]
        );

        $id = EventBandExtension::getPublisherId('event');
        $publisher = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Transport\Amqp\AmqpPublisher', $publisher);
        $definition = $this->container->getDefinition($id);
        $this->assertEquals(EventBandExtension::getAmqpDriverId('con1'), (string) $definition->getArgument(0));
        $this->assertEquals(EventBandExtension::getAmqpConverterId('foo'), (string) $definition->getArgument(1));
        $this->assertEquals('test', $definition->getArgument(2));
        $this->assertEquals(EventBandExtension::getRouterId('router1'), $definition->getArgument(3));
        $this->assertFalse($definition->getArgument(4));
        $this->assertTrue($definition->getArgument(5));
        $this->assertTrue($definition->getArgument(6));
    }

    /**
     * @test publish subscriber is registered for events
     */
    public function publisherListener()
    {
        $this->loadExtension(
            [
                'publishers' => [
                    'event' => [
                        'events' => ['event.test1', 'event.test2'],
                        'propagation' => false,
                        'priority' => 666
                    ]
                ]
            ]
        );

        $id = EventBandExtension::getListenerId('event');

        $definition = $this->container->getDefinition($id);
        $this->assertEquals(EventBandExtension::getPublisherId('event'), (string) $definition->getArgument(0));
        $this->assertFalse($definition->getArgument(1));

        $this->assertTrue($definition->hasTag('event_band.subscription'));
        $tags = $definition->getTag('event_band.subscription');
        $this->assertCount(2, $tags);
        $this->assertEquals($tags[1]['event'], 'event.test2');
        $this->assertEquals($tags[1]['priority'], 666);
    }

    /**
     * @test consumer amqp transport config
     */
    public function amqpConsumers()
    {
        $this->loadExtension(
            [
                'transports' => [
                    'amqp' => [
                        'connections' => [
                            'con1' => []
                        ],
                        'converters' => [
                            'foo' => [
                                'serialize' => []
                            ]
                        ]
                    ]
                ],
                'routers' => [
                    'router1' => [
                        'pattern' => '{param}'
                    ]
                ],
                'consumers' => [
                    'event' => [
                        'transport' => [
                            'amqp' => [
                                'connection' => 'con1',
                                'converter' => 'foo',
                                'queue' => 'test'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $id = EventBandExtension::getConsumerId('event');
        $consumer = $this->container->get($id);
        $this->assertInstanceOf('EventBand\Transport\Amqp\AmqpConsumer', $consumer);
        $definition = $this->container->getDefinition($id);
        $this->assertEquals(EventBandExtension::getAmqpDriverId('con1'), (string) $definition->getArgument(0));
        $this->assertEquals(EventBandExtension::getAmqpConverterId('foo'), (string) $definition->getArgument(1));
        $this->assertEquals('test', $definition->getArgument(2));
    }

    private function loadExtension(array $config, $compile = true)
    {
        $config = array_merge(['transports' => ['amqp' => []]], $config);
        $this->extension->load(['event_band' => $config], $this->container);

        $this->container->getCompilerPassConfig()->setRemovingPasses(array());
        if ($compile) {
            $this->container->compile();
        }
    }
}
