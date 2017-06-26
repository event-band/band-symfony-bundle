<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SubscriptionCompiler
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SubscribePass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dispatcher = $container->findDefinition('event_dispatcher');

        foreach ($container->findTaggedServiceIds('event_band.subscription') as $id => $subscriptions) {
            foreach ($subscriptions as $subscription) {
                $dispatcher->addMethodCall('subscribe', [
                    new Definition('EventBand\Bundle\ServiceSubscription', [
                        $subscription['event'],
                        new Reference('service_container'),
                        $id,
                        empty($subscription['method']) ? '' : $subscription['method'],
                        empty($subscription['band']) ? '' : $subscription['band']
                    ],
                    empty($subscription['priority']) ? 0 : $subscription['priority']
                )]);
            }
        }
    }
}