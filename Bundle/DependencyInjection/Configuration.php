<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DependencyInjection;

use EventBand\Transport\Amqp\Definition\ExchangeDefinition;
use EventBand\Transport\Amqp\Definition\ExchangeType;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
        $transportSections = [
            new AmqpConfiguration()
        ];

        $sections = [
            new SerializerConfiguration(),
            new RouterConfiguration(),
            new PublisherConfiguration($transportSections),
            new ConsumerConfiguration($transportSections)
        ];

        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('event_band');

        $transports = $root->children()->arrayNode('transports')
            ->cannotBeEmpty()
            ->isRequired()
            ->validate()
                ->always(function ($transports) {
                    if (empty($transports)) {
                        throw new InvalidConfigurationException('Transport should be defined');
                    }

                    return $transports;
                })
            ->end()
        ;

        /** @var SectionConfiguration $section */
        foreach ($transportSections as $section) {
            $transports->append($section->getSectionDefinition());
        }

        foreach ($sections as $section) {
            $root->append($section->getSectionDefinition());
        }

        return $treeBuilder;
    }
}
