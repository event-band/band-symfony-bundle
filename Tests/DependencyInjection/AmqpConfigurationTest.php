<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\AmqpConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class AmqpConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AmqpConfigurationTest extends SectionConfigurationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createSection()
    {
        return new AmqpConfiguration();
    }

    /**
     * @test default values for empty config
     */
    public function defaultValues()
    {
        $config = $this->processSection([]);

        $this->assertEquals(
            [
                'driver' => 'amqplib',
                'connections' => [
                    'default' => AmqpConfiguration::getDefaultConnection()
                ],
                'converters' => [
                    'default' => [
                        'type' => 'serialize',
                        'parameters' => [
                            'serializer' => 'default'
                        ]
                    ]
                ]
            ],
            $config
        );
    }

    /**
     * @test all connection options can be set
     */
    public function connectionOptions()
    {
        $connections = [
            'default' => [
                'connection' => [
                    'servers' => [
                        [
                            'host' => 'def',
                            'port' => '100',
                            'virtual_host' => 'vh',
                            'user' => 'user',
                            'password' => 'pass',
                        ]
                    ],
                    'strategy' => 'RoundRobin',
                ],
                'exchanges' => [],
                'queues' => []
            ],
            'foo' => [
                'connection' => [
                    'servers' => [
                        [
                            'host' => 'foo',
                            'port' => '200',
                            'virtual_host' => 'vh_foo',
                            'user' => 'user_foo',
                            'password' => 'pass_foo',
                        ]
                    ],
                    'strategy' => 'RoundRobin',
                ],
                'exchanges' => [],
                'queues' => []
            ]
        ];
        $config = $this->processSection([
            'connections' => $connections
        ]);

        $this->assertEquals($connections, $config['connections']);
    }

    /**
     * @test drivers are amqplib and pecl
     * @dataProvider drivers
     */
    public function driverRestrictions($value, $valid)
    {
        try {
            $config = $this->processSection([
                'driver' => $value
            ]);
        } catch (InvalidConfigurationException $e) {
            if ($valid) {
                $this->fail('Valid driver failed');
            }

            return;
        }

        if (!$valid) {
            $this->fail('Invalid driver passed');
        }

        $this->assertEquals($value, $config['driver']);
    }

    public function drivers()
    {
        return [
            ['amqplib', true],
            ['pecl', true],
            ['foo', false]
        ];
    }

    /**
     * @test connections..exchanges with default values
     */
    public function amqpExchangesDefaultConfig()
    {
        $config = [
            'connections' => [
                'default' => [
                    'exchanges' => [
                        'foo.bar' => []
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            [
                'type' => 'topic',
                'transient' => false,
                'auto_delete' => false,
                'internal' => false,
                'bind' => []
            ],
            $this->processSection($config)['connections']['default']['exchanges']['foo.bar']
        );
    }

    /**
     * @test connections..queues with default values
     */
    public function amqQueuesDefaultConfig()
    {
        $config = [
            'connections' => [
                'default' => [
                    'queues' => [
                        'foo.bar' => []
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            [
                'transient' => false,
                'auto_delete' => false,
                'exclusive' => false,
                'bind' => [],
                'arguments' => null,
            ],
            $this->processSection($config)['connections']['default']['queues']['foo.bar']
        );
    }

    /**
     * @test connections.%.exchanges.%.bindings formats
     * @dataProvider bindings
     */
    public function exchangesBindingsConfig($routingConfig, array $routingKeys)
    {
        $config = [
            'connections' => [
                'default' => [
                    'exchanges' => [
                        'foo.bar' => [
                            'bind' => [
                                'bar.baz' => $routingConfig
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $routingKeys,
            $this->processSection($config)['connections']['default']['exchanges']['foo.bar']['bind']['bar.baz']
        );
    }

    public function bindings()
    {
        return [
            ['', ['']],
            [['foo', 'bar'], ['foo', 'bar']],
            ['foo', ['foo']],
            [[], ['']],
            [['foo', 'foo'], ['foo']]
        ];
    }

    /**
     * @test publisher transport defaults
     */
    public function publisherTransportDefaults()
    {
        /** @var AmqpConfiguration $section */
        $section = $this->getSection();
        $this->assertEquals(
            [
                'connection' => 'default',
                'exchange' => null,
                'router' => 'default',
                'converter' => 'default',
                'persistent' => true,
                'mandatory' => false,
                'immediate' => false
            ],
            $this->processNode($section->getPublisherNode(), [])
        );
    }

    /**
     * @test consumer transport defaults
     */
    public function consumerTransportDefaults()
    {
        /** @var AmqpConfiguration $section */
        $section = $this->getSection();
        $this->assertEquals(
            [
                'connection' => 'default',
                'queue' => null,
                'converter' => 'default'
            ],
            $this->processNode($section->getConsumerNode(), [])
        );
    }
}
