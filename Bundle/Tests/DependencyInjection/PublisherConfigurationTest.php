<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\PublisherConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class PublisherConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class PublisherConfigurationTest extends SectionConfigurationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $transport1;
    /**
     * @var ArrayNodeDefinition
     */
    private $root1;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $transport2;
    /**
     * @var ArrayNodeDefinition
     */
    private $root2;

    protected function setUp()
    {
        $this->transport1 = $this->getMock('EventBand\Bundle\DependencyInjection\TransportSectionConfiguration');
        $this->transport1
            ->expects($this->once())
            ->method('getPublisherNode')
            ->will($this->returnValue($this->root1 = (new TreeBuilder())->root('transport1')))
        ;

        $this->transport2 = $this->getMock('EventBand\Bundle\DependencyInjection\TransportSectionConfiguration');
        $this->transport2
            ->expects($this->once())
            ->method('getPublisherNode')
            ->will($this->returnValue($this->root2 = (new TreeBuilder())->root('transport2')))
        ;
    }


    /**
     * {@inheritDoc}
     */
    protected function createSection()
    {
        return new PublisherConfiguration([$this->transport1, $this->transport2]);
    }

    /**
     * @test default values
     */
    public function defaultValues()
    {
        $this->assertEquals(
            [
                'pub' => [
                    'events' => ['foo'],
                    'transport' => [
                        'type' => 'transport1',
                        'parameters' => []
                    ],
                    'propagation' => true,
                    'priority' => 1024
                ]
            ],
            $this->processSection([
                'pub' => [
                    'events' => ['foo']
                ]
            ])
        );
    }

    /**
     * @test events configuration formats
     * @dataProvider events
     *
     * @param mixed         $configValue Value from config
     * @param array|boolean $result      If result is false config is wrong
     */
    public function eventFormat($configValue, $result)
    {
        try {
            $config = $this->processSection([
                'pub' => [
                    'events' => $configValue
                ]
            ]);
        } catch (InvalidConfigurationException $e) {
            if ($result) {
                $this->fail('Value should be applicable');
            }

            return;
        }

        if (!$result) {
            $this->fail('Value is wrong');
        }

        $this->assertEquals($result, $config['pub']['events']);
    }

    public function events()
    {
        return [
            ['foo', ['foo']],
            [['foo', 'bar'], ['foo', 'bar']],
            [null, false],
            [[], false],
            ['', false],
            [['', 'foo'], false],
            [['foo', 'foo'], ['foo']]
        ];
    }

    /**
     * @test if transport is not set default (first) is used
     */
    public function defaultTransport()
    {
        $this->root1
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('param1')->defaultValue('value1')->end()
                ->scalarNode('param2')->defaultValue('value2')->end()
            ->end()
        ;

        $config = $this->processSection([
            'pub1' => [
                'events' => ['foo']
            ],
            'pub2' => [
                'events' => ['bar']
            ]
        ]);

        $transport = [
            'type' => 'transport1',
            'parameters' => [
                'param1' => 'value1',
                'param2' => 'value2'
            ]
        ];
        $this->assertEquals($transport, $config['pub1']['transport']);
        $this->assertEquals($transport, $config['pub2']['transport']);
    }

    /**
     * @test exception on several transports
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function severalTransports()
    {
        $this->processSection([
            'pub1' => [
                'events' => ['foo'],
                'transport' => [
                    'transport1' => [],
                    'transport2' => []
                ]
            ]
        ]);
    }
}
