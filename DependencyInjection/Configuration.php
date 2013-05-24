<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for EventBandBundle extension
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('event_band')->children();

        $this
            ->addSerializersSection($root)
            ->addRoutersSection($root)
            ->addTransportsSection($root)
            ->addPublishersSection($root)
            ->addConsumersSection($root)

        ;

        return $treeBuilder;
    }

    private function addTransportsSection(NodeBuilder $builder)
    {
        $node = $builder->arrayNode('transports')->cannotBeEmpty()->children();

        $this->addAmqpSection($node);

        $node->end();

        return $this;
    }

    /**
     * @param NodeBuilder $builder
     *
     * @return $this
     */
    private function addSerializersSection(NodeBuilder $builder)
    {
        $defaults = array(
            'adapter' => 'native'
        );
        $builder
            ->arrayNode('serializers')->info('Native serializer with name "default" is added')
                ->useAttributeAsKey('name')
                ->addDefaultChildrenIfNoneSet('default')
                ->prototype('array')
                    ->children()
                        ->scalarNode('adapter')->defaultValue($defaults['adapter'])->end()
                        ->arrayNode('parameters')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('format')->defaultValue('json')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->validate()
                    ->always()
                    ->then(function ($serializers) use ($defaults) {
                        if (!isset($serializers['default'])) {
                            $serializers['default'] = $defaults;
                        }

                        return $serializers;
                    })
                ->end()
            ->end()
        ;

        return $this;
    }

    /**
     * @param NodeBuilder $builder
     *
     * @return $this
     */
    private function addRoutersSection(NodeBuilder $builder)
    {
        $defaults = array(
            'type' => 'pattern',
            'parameters' => array(
                'pattern' => '{name}'
            )
        );
        $builder
            ->arrayNode('routers')->info('Pattern router with name "default" is added')
                ->useAttributeAsKey('name')
                ->addDefaultChildrenIfNoneSet('default')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->defaultValue($defaults['type'])->end()
                        ->arrayNode('parameters')->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('pattern')->defaultValue($defaults['parameters']['pattern'])->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->validate()
                    ->always()
                    ->then(function ($routers) use ($defaults) {
                        if (!isset($routers['default'])) {
                            $routers['default'] = $defaults;
                        }

                        return $routers;
                    })
                ->end()
            ->end()
        ;

        return $this;
    }

    /**
     * @param NodeBuilder $builder
     *
     * @return $this
     */
    private function addPublishersSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('publishers')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('adapter')->defaultValue('amqp')->end()
                        ->arrayNode('events')
                            ->isRequired()
                            ->canNotBeEmpty()
                            ->prototype('scalar')->end()
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) { return array($v); })
                            ->end()
                        ->end()
                        ->booleanNode('propagation')->defaultTrue()->end()
                        ->scalarNode('priority')->defaultValue(1024)->end()
                        ->arrayNode('parameters')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('connection')->defaultValue('default')->end()
                                ->scalarNode('exchange')->defaultNull()->info('Default exchange name is a publisher name')->end()
                                ->scalarNode('router')->defaultValue('default')->end()
                                ->scalarNode('converter')->defaultValue('default')->end()
                                ->booleanNode('persistent')->defaultTrue()->end()
                                ->booleanNode('mandatory')->defaultFalse()->end()
                                ->booleanNode('immediate')->defaultFalse()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->always(function ($publishers) {
                        if (!is_array($publishers)) {
                            return $publishers;
                        }
                        // Use publisher name as exchange name for amqp
                        foreach ($publishers as $name => &$publisher) {
                            if (!isset($publisher['parameters']['exchange']) || $publisher['parameters']['exchange'] === null) {
                                $publisher['parameters']['exchange'] = $name;
                            }

                        }

                        return $publishers;
                    })
                ->end()
            ->end()
        ;

        return $this;
    }

    /**
     * @param NodeBuilder $builder
     *
     * @return $this
     */
    private function addConsumersSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('consumers')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('adapter')->defaultValue('amqp')->end()
                        ->arrayNode('parameters')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('connection')->defaultValue('default')->end()
                                ->scalarNode('converter')->defaultValue('default')->end()
                                ->scalarNode('queue')->defaultNull()->info('Default queue name is a consumer name')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->beforeNormalization()
                    ->always(function ($dispatchers) {
                        if (!is_array($dispatchers)) {
                            return $dispatchers;
                        }
                        // Use publisher name as exchange name for amqp
                        foreach ($dispatchers as $name => &$dispatcher) {
                            if (!isset($dispatcher['parameters']['queue']) || $dispatcher['parameters']['queue'] === null) {
                                $dispatcher['parameters']['queue'] = $name;
                            }

                        }

                        return $dispatchers;
                    })
                ->end()
            ->end()
        ;

        return $this;
    }

    /**
     * @param NodeBuilder $builder
     *
     * @return $this
     */
    private function addAmqpSection(NodeBuilder $builder)
    {
        $defaultConnection = [
            'host' => 'localhost',
            'port' => '5672',
            'virtual_host' => '/',
            'user' => 'guest',
            'password' => 'guest'
        ];
        $defaultConverter = [
            'type' => 'serialize'
        ];
        $builder
            ->arrayNode('amqp')
                ->children()
                    ->scalarNode('driver')->defaultValue('amqp')
                        ->validate()
                            ->ifNotInArray(array('amqplib', 'pecl'))
                            ->thenInvalid('Unknown "%s" driver. Valid drivers: "amqplib", "pecl"')
                        ->end()
                    ->end()
                    ->arrayNode('connections')->info('Connection with name "default" is added')
                        ->useAttributeAsKey('name')
                        ->addDefaultChildrenIfNoneSet('default')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('host')->defaultValue($defaultConnection['host'])->end()
                                ->scalarNode('port')->defaultValue($defaultConnection['port'])->end()
                                ->scalarNode('virtual_host')->defaultValue($defaultConnection['virtual_host'])->end()
                                ->scalarNode('user')->defaultValue($defaultConnection['user'])->end()
                                ->scalarNode('password')->defaultValue($defaultConnection['password'])->end()
                            ->end()
                        ->end()
                        ->validate()
                            // Add default connection
                            ->always()
                            ->then(function ($connections) use ($defaultConnection) {
                                if (!isset($connections['default'])) {
                                    $connections['default'] = $defaultConnection;

                                }

                                return $connections;
                            })
                        ->end()
                    ->end()
                    ->arrayNode('converters')->info('Serialize converter with name "default" is added')
                        ->useAttributeAsKey('name')
                        ->addDefaultChildrenIfNoneSet('default')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('type')->defaultValue($defaultConverter['type'])->end()
//                                ->arrayNode('parameters')->end()
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $this;
    }
}
