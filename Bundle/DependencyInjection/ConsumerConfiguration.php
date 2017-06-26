<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class ConsumerConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ConsumerConfiguration implements SectionConfiguration
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
        $root = $builder->root('consumers')->useAttributeAsKey('name');

        /** @var ArrayNodeDefinition $transport */
        $transport = $root->prototype('array')->children()->arrayNode('transport');
        $default = null;
        foreach ($this->transportSections as $section) {
            $node = $section->getConsumerNode();
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
                ->always(function ($consumers) use ($default) {
                    if (!is_array($consumers)) {
                        return [];
                    }

                    foreach ($consumers as $name => &$publisher) {
                        // Use amqp if transport is not defined
                        if (empty($publisher['transport'])) {
                            $publisher['transport'] = [$default => []];
                        }

                        // TODO: Write some generic logic for default values
                        // Use publisher name as exchange name for amqp
                        if (isset($publisher['transport']['amqp']) && empty($publisher['transport']['amqp']['queue'])) {
                            $publisher['transport']['amqp']['queue'] = $name;
                        }
                    }

                    return $consumers;
                })
            ->end()
        ;

        return $root;
    }
}