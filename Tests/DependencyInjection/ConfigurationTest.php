<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\Configuration;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Setup configuration
     */
    protected function setUp()
    {
        $this->config = new Configuration();
    }

    /**
     * @test amqp.connections.%.exchanges with default values
     */
    public function amqpExchangesDefaultConfig()
    {
        $config = [
            'transports' => [
                'amqp' => [
                    'connections' => [
                        'default' => [
                            'exchanges' => [
                                'foo.bar' => []
                            ]
                        ]
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
            $this->processConfiguration([$config])['transports']['amqp']['connections']['default']['exchanges']['foo.bar']
        );
    }

    /**
     * @test amqp.connections.%.exchanges.%.bindings formats
     * @dataProvider bindings
     */
    public function amqpExchangesBindingsConfig($routingConfig, array $routingKeys)
    {
        $config = [
            'transports' => [
                'amqp' => [
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
                ]
            ]
        ];

        $this->assertEquals(
            $routingKeys,
            $this->processConfiguration([$config])['transports']['amqp']['connections']['default']['exchanges']['foo.bar']['bind']['bar.baz']
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

    private function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this->config, $configs);
    }
}
