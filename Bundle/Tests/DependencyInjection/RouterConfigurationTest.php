<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\RouterConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class RouterConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class RouterConfigurationTest extends SectionConfigurationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createSection()
    {
        return new RouterConfiguration();
    }

    /**
     * @test If no configuration is set default native serializer is added
     */
    public function defaultValues()
    {
        $config = $this->processSection([]);

        $this->assertEquals(
            [
                'default' => [
                    'type' => 'pattern',
                    'parameters' => '{name}'
                ]
            ],
            $config
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
                    'type' => 'pattern',
                    'parameters' => '{name}'
                ]
            ],
            $this->processNode($root, [])['routers']
        );
    }

    /**
     * @test pattern section is converted to ["type" => "pattern", "parameters" => pattern]
     */
    public function patternConfig()
    {
        $config = $this->processSection([
            'default' => [
                'pattern' => '{foo}'
            ]
        ]);

        $this->assertEquals(
            [
                'default' => [
                    'type' => 'pattern',
                    'parameters' => '{foo}'
                ]
            ],
            $config
        );
    }

    /**
     * @test default pattern router is always added
     */
    public function defaultPatternConfig()
    {
        $config = $this->processSection([
            'foo' => [
                'pattern' => '{foo}'
            ]
        ]);

        $this->assertEquals(
            [
                'foo' => [
                    'type' => 'pattern',
                    'parameters' => '{foo}'
                ],
                'default' => [
                    'type' => 'pattern',
                    'parameters' => '{name}'
                ]

            ],
            $config
        );
    }
}
