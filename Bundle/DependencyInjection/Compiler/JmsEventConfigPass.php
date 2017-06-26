<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class JmsEventConfigPass
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class JmsEventConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('jms_serializer.metadata.file_locator')) {
            $fileLocator = $container->getDefinition('jms_serializer.metadata.file_locator');
            $directories = $fileLocator->getArgument(0);
            $directories['Symfony\Component\EventDispatcher'] = dirname(dirname(__DIR__)) . '/Resources/config/serializer';
            $fileLocator->replaceArgument(0, $directories);
        }
    }
}