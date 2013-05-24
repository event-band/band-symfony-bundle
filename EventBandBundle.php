<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle;

use EventBand\Bundle\DependencyInjection\Compiler\JmsEventConfigPass;
use EventBand\Bundle\DependencyInjection\Compiler\ReplaceDispatcherPass;
use EventBand\Bundle\DependencyInjection\Compiler\SubscribeKernelListenerPass;
use EventBand\Bundle\DependencyInjection\Compiler\SubscribePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * EventBandBundle main bundle class
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class EventBandBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SubscribeKernelListenerPass(), PassConfig::TYPE_REMOVE); // RegisterKernelListenersPass is registered on "AFTER_REMOVING"
        $container->addCompilerPass(new SubscribePass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new ReplaceDispatcherPass(), PassConfig::TYPE_BEFORE_REMOVING);

        $container->addCompilerPass(new JmsEventConfigPass());
    }
}
