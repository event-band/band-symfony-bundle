<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection;

use EventBand\Transport\Amqp\Definition\ExchangeType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class AmqpConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AmqpConfiguration implements TransportSectionConfiguration
{
    private static $DEFAULT_CONNECTION = [
        'host' => 'localhost',
        'port' => '5672',
        'virtual_host' => '/',
        'user' => 'guest',
        'password' => 'guest',
        'exchanges' => [],
        'queues' => []
    ];

    /**
     * Get default connection options
     *
     * @return array
     */
    public static function getDefaultConnection()
    {
        return self::$DEFAULT_CONNECTION;
    }

    /**
     * {@inheritDoc}
     */
    public function getSectionDefinition()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('amqp');

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('driver')->values(['amqplib', 'pecl'])->defaultValue('amqplib')->end()
                ->booleanNode('use_tracer_interceptor')->defaultFalse()->cannotBeEmpty()->end()
                ->arrayNode('connections')->info('Connection with name "default" is added')
                    ->useAttributeAsKey('name')
                    ->addDefaultChildrenIfNoneSet('default')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->defaultValue(self::$DEFAULT_CONNECTION['host'])->end()
                            ->scalarNode('port')->defaultValue(self::$DEFAULT_CONNECTION['port'])->end()
                            ->scalarNode('virtual_host')->defaultValue(self::$DEFAULT_CONNECTION['virtual_host'])->end()
                            ->scalarNode('user')->defaultValue(self::$DEFAULT_CONNECTION['user'])->end()
                            ->scalarNode('password')->defaultValue(self::$DEFAULT_CONNECTION['password'])->end()
                            ->arrayNode('exchanges')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('type')->defaultValue(ExchangeType::TOPIC)
                                            ->validate()
                                                ->ifNotInArray(ExchangeType::getTypes())
                                                ->thenInvalid(sprintf('Unknown "%s" exchange type. Valid types: %s', '%s', implode(', ', ExchangeType::getTypes())))
                                            ->end()
                                        ->end()
                                        ->booleanNode('transient')->defaultFalse()->end()
                                        ->booleanNode('auto_delete')->defaultFalse()->end()
                                        ->booleanNode('internal')->defaultFalse()->end()
                                    ->end()
                                    ->append($this->amqpBindingNode())
                                ->end()
                            ->end()
                            ->arrayNode('queues')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->booleanNode('transient')->defaultFalse()->end()
                                        ->booleanNode('auto_delete')->defaultFalse()->end()
                                        ->booleanNode('exclusive')->defaultFalse()->end()
                                        ->variableNode('arguments')->defaultValue(null)->end()
                                    ->end()
                                    ->append($this->amqpBindingNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        // Add default connection
                        ->always()
                        ->then(function ($connections) {
                            if (!isset($connections['default'])) {
                                $connections['default'] = self::$DEFAULT_CONNECTION;
                            }

                            return $connections;
                        })
                    ->end()
                ->end()
                ->arrayNode('converters')->info('Serialize converter with name "default" is added')
                    ->defaultValue(['default' => ['type' => 'serialize', 'parameters' => ['serializer' => 'default']]])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('serialize')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('serializer')->defaultValue('default')->end()
                                    // TODO: add message prototype parameters
                                ->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->always()
                            ->then(function ($converter) {
                                if (count($converter) !== 1) {
                                    throw new \InvalidArgumentException(sprintf(
                                        'Expected only 1 converter type. Got %d: %s',
                                        count($converter), implode(', ', array_keys($converter))
                                    ));
                                }

                                return ['type' => key($converter), 'parameters' => current($converter)];
                            })
                        ->end()
                    ->end()
                    ->validate()
                        ->always()
                        ->then(function ($router) {
                            if (!isset($router['default'])) {
                                $router['default'] = ['type' => 'serialize', 'parameters' => ['serializer' => 'default']];
                            }

                            return $router;
                        })
                    ->end()
                ->end()
            ->end()
        ;


        return $root;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublisherNode()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('amqp');

        $root
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
        ;

        return $root;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsumerNode()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('amqp');

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('connection')->defaultValue('default')->end()
                ->scalarNode('converter')->defaultValue('default')->end()
                ->scalarNode('queue')->defaultNull()->info('Default queue name is a consumer name')->end()
            ->end()
        ;

        return $root;
    }

    private function amqpBindingNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('bind');

        $node
            ->useAttributeAsKey('source')
            ->prototype('array')
                ->prototype('scalar')->end()
                ->beforeNormalization()
                    ->ifString()->then(function ($v) { return [$v]; })
                ->end()
                ->beforeNormalization()
                    ->ifArray()->then(function (array $v) { return empty($v) ? [''] : array_unique($v); })
                ->end()
            ->end()
        ;

        return $node;
    }
}