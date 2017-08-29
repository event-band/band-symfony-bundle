<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\Command;

use EventBand\Adapter\Symfony\Command\AbstractDispatchCommand;

/**
 * Description of DispatchCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class DispatchCommand extends AbstractDispatchCommand
{
    private $container;

    /**
     * {@inheritDoc}
     */
    protected function getConsumer($band)
    {
        return $this->getContainer()->get(sprintf('event_band.consumer.%s', $band));
    }

    /**
     * {@inheritDoc}
     */
    protected function getDispatcher()
    {
        return $this->getContainer()->get('event_band.dispatcher');
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('event-band:dispatch');
    }

    protected function getDefaultTimeout()
    {
        return $this->getContainer()->getParameter('event_band.default_idle_timeout');
    }

    protected function getDefaultMaxExecutionTime()
    {
        return $this->getContainer()->getParameter('event_band.default_max_execution_time');
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            /** @var $app \Symfony\Bundle\FrameworkBundle\Console\Application */
            $app = $this->getApplication();
            $this->container = $app->getKernel()->getContainer();
        }

        return $this->container;
    }
}
