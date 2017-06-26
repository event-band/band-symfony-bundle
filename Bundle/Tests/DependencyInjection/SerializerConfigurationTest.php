<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\SerializerConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class SerializerConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SerializerConfigurationTest extends SectionConfigurationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createSection()
    {
        return new SerializerConfiguration();
    }

    /**
     * @test If no configuration is set default native serializer is added
     */
    public function defaultValues()
    {
        $this->assertEquals(
            [
                'default' => [
                    'type' => 'native',
                    'parameters' => []
                ]
            ],
            $this->processSection([])
        );
    }

    /**
     * @test If section is not set default values are still added
     */
    public function sectionDefaultValues()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('test')->append($this->getSection()->getSectionDefinition());

        $this->assertEquals(
            [
                'default' => [
                    'type' => 'native',
                    'parameters' => []
                ]
            ],
            $this->processNode($root, [])['serializers']
        );
    }

    /**
     * @test native section is converted to ["type" => "native", "parameters" => []]
     */
    public function nativeConfig()
    {
        $config = $this->processSection([
            'default' => [
                'native' => null
            ]
        ]);

        $this->assertEquals(
            [
                'default' => [
                    'type' => 'native',
                    'parameters' => []
                ]
            ],
            $config
        );
    }

    /**
     * @test if no default serializer configured, default is native
     */
    public function defaultNativeSerializer()
    {
        $config = $this->processSection([
            'foo' => [
                'native' => []
            ]
        ]);

        $this->assertEquals(
            [
                'foo' => [
                    'type' => 'native',
                    'parameters' => []
                ],
                'default' => [
                    'type' => 'native',
                    'parameters' => []
                ]
            ],
            $config
        );
    }

    /**
     * @test jms section is converted to ["type" => "jms", "parameters" => [...]]
     */
    public function jmsConfig()
    {
        $config = $this->processSection([
            'default' => [
                'jms' => [
                    'format' => 'xml'
                ]
            ]
        ]);

        $this->assertEquals(
            [
                'default' => [
                    'type' => 'jms',
                    'parameters' => ['format' => 'xml']
                ]
            ],
            $config
        );
    }
}
