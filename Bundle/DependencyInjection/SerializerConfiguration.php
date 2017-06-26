<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class SerializerConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SerializerConfiguration implements SectionConfiguration
{
    /**
     * @return ArrayNodeDefinition
     */
    public function getSectionDefinition()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('serializers');
        $root->info('Native serializer with name "default" is added');

        $root
            ->useAttributeAsKey('name')
            ->defaultValue(['default' => ['type' => 'native', 'parameters' => []]])
            ->prototype('array')
                ->append($this->nativeNode())
                ->append($this->jmsNode())
                ->validate()
                    ->always(function ($serializer) {
                        if (count($serializer) !== 1) {
                            throw new \InvalidArgumentException(sprintf(
                                'Expected only 1 adapter. Got %d: %s',
                                count($serializer), implode(', ', array_keys($serializer))
                            ));
                        }

                        return ['type' => key($serializer), 'parameters' => current($serializer)];
                    })
                ->end()
            ->end()
            ->validate()
                ->always(function ($serializers) {
                    if (empty($serializers['default'])) {
                        $serializers['default'] = ['type' => 'native', 'parameters' => []];
                    }

                    return $serializers;
                })
            ->end()
        ;

        return $root;
    }

    private function nativeNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('native');

        return $node;
    }

    private function jmsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('jms');
        $node
            ->children()
                ->scalarNode('format')->defaultValue('json')->end()
            ->end()
        ;

        return $node;
    }
}