<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class PublisherConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class PublisherConfiguration implements SectionConfiguration
{
    private $transportSections;

    /**
     * @param TransportSectionConfiguration[] $transportSections
     */
    public function __construct(array $transportSections)
    {
        if (empty($transportSections)) {
            throw new \InvalidArgumentException('Sections should not be empty');
        }
        $this->transportSections = $transportSections;
    }

    /**
     * {@inheritDoc}
     */
    public function getSectionDefinition()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('publishers')->useAttributeAsKey('name');
        $prototype = $root->prototype('array')
            ->children()
                ->arrayNode('events')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) { return [$v]; })
                    ->end()
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function (array $v) { return array_unique($v); })
                    ->end()
                ->end()
                ->booleanNode('propagation')->defaultTrue()->end()
                ->scalarNode('priority')->defaultValue(1024)->end()
            ->end()
        ;

        /** @var ArrayNodeDefinition $transport */
        $transport = $prototype->children()->arrayNode('transport');
        $default = null;
        foreach ($this->transportSections as $section) {
            $node = $section->getPublisherNode();
            if (!$default) {
                $default = $node->getNode(true)->getName();
            }
            $transport->append($node);
        }

        $transport->validate()->always(function (array $transport) {
            if (count($transport) !== 1) {
                throw new InvalidConfigurationException('Transport should be exactly one');
            }

            reset($transport);
            return ['type' => key($transport), 'parameters' => current($transport)];
        });

        $root
            ->beforeNormalization()
                ->always(function ($publishers) use ($default) {
                    if (!is_array($publishers)) {
                        return [];
                    }

                    foreach ($publishers as $name => &$publisher) {
                        // Use amqp if transport is not defined
                        if (empty($publisher['transport'])) {
                            $publisher['transport'] = [$default => []];
                        }

                        // TODO: Write some generic logic for default values
                        // Use publisher name as exchange name for amqp
                        if (isset($publisher['transport']['amqp']) && empty($publisher['transport']['amqp']['exchange'])) {
                            $publisher['transport']['amqp']['exchange'] = $name;
                        }
                    }

                    return $publishers;
                })
            ->end()
        ;

        return $root;
    }
}
