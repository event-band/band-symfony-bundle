<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class InitBandPass
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ReplaceDispatcherPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dispatcher = $container->getDefinition('event_dispatcher');
        $dispatcher->setPublic(false);

        $container->setDefinition('event_band.internal_dispatcher', $dispatcher);
        $container->setDefinition('event_dispatcher', $container->getDefinition('event_band.dispatcher'));
        $container->removeDefinition('event_band.dispatcher');
        $container->setAlias('event_band.dispatcher', 'event_dispatcher');
    }
}