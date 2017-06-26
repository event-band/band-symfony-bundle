<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\ConsumerConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class ConsumerConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ConsumerConfigurationTest extends SectionConfigurationTestCase
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
            ->method('getConsumerNode')
            ->will($this->returnValue($this->root1 = (new TreeBuilder())->root('transport1')))
        ;

        $this->transport2 = $this->getMock('EventBand\Bundle\DependencyInjection\TransportSectionConfiguration');
        $this->transport2
            ->expects($this->once())
            ->method('getConsumerNode')
            ->will($this->returnValue($this->root2 = (new TreeBuilder())->root('transport2')))
        ;
    }

    /**
     * @return ConsumerConfiguration
     */
    protected function createSection()
    {
        return new ConsumerConfiguration([$this->transport1, $this->transport2]);
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
            'sub1' => [],
            'sub2' => []
        ]);

        $transport = [
            'type' => 'transport1',
            'parameters' => [
                'param1' => 'value1',
                'param2' => 'value2'
            ]
        ];
        $this->assertEquals($transport, $config['sub1']['transport']);
        $this->assertEquals($transport, $config['sub2']['transport']);
    }

    /**
     * @test exception on several transports
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function severalTransports()
    {
        $this->processSection([
            'sub1' => [
                'transport' => [
                    'transport1' => [],
                    'transport2' => []
                ]
            ]
        ]);
    }
}
