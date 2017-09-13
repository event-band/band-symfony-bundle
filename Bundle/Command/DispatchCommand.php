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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of DispatchCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class DispatchCommand extends AbstractDispatchCommand
{
    private $container;

    protected $bandName;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bandName = $input->getArgument('band');
        $stopCallback = function ($signo) use ($bandName, $output) {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln(sprintf('Got stop signal "%s". Trying to stop consume.', $signo));
            }
            $consumer = $this->getConsumer($bandName);
            $consumer->stop();
        };
        foreach ($this->getStopSignals() as $signal) {
            $this->addSignalCallback($signal, $stopCallback);
        }
        parent::execute($input, $output);
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
