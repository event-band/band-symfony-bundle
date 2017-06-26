<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * Description of AsyncEventListenerPass
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class SubscribeKernelListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configurator = $container->getDefinition('event_band.dispatcher.configurator');

        // Process listeners
        foreach ($container->findTaggedServiceIds('kernel.event_listener') as $id => $events) {
            $def = $container->getDefinition($id);
            $tags = $def->getTags();
            $events = $tags['kernel.event_listener']; // Ensure events with same indexes
            foreach ($events as $i => $event) {
                if (!isset($event['band'])) {
                    continue;
                }

                $band = $event['band'];
                if ($band === '') {
                    continue;
                }

                if (!isset($event['event'])) {
                    // If event is not set we just ignore. RegisterKernelListenersPass will throw an exception
                    continue;
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace(array(
                        '/(?<=\b)[a-z]/ie',
                        '/[^a-z0-9]/i'
                    ), array('strtoupper("\\0")', ''), $event['event']);
                }

                $configurator->addMethodCall(
                    'addListenerService',
                    [$id, $event['event'], $event['method'], $band, isset($event['priority']) ? $event['priority'] : 0]
                );
                unset($tags['kernel.event_listener'][$i]);

            }

            $def->setTags($tags);
        }

        // Process subscribers
        foreach ($container->findTaggedServiceIds('kernel.event_subscriber') as $id => $attributes) {
            $attributes = $attributes[0];
            if (!isset($attributes['band'])) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            $configurator->addMethodCall('addSubscriberService', [$class, $id, $attributes['band']]);
            $definition->clearTag('kernel.event_subscriber');
        }
    }
}
