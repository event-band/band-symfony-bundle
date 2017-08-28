<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony\Command;

use Che\ConsoleSignals\SignaledCommand;
use EventBand\BandDispatcher;
use EventBand\CallbackSubscription;
use EventBand\Processor\Control\EventLimiter;
use EventBand\Processor\Control\TimeLimiter;
use EventBand\Processor\DispatchProcessor;
use EventBand\Processor\DispatchStopEvent;
use EventBand\Processor\DispatchTimeoutEvent;
use EventBand\Processor\StoppableDispatchEvent;
use EventBand\Transport\EventConsumer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractDispatchCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
abstract class AbstractDispatchCommand extends SignaledCommand
{
    private $maxExecutionTime;
    private $idleTimeout;
    private $startTime;

    /**
     * @return BandDispatcher
     */
    abstract protected function getDispatcher();

    /**
     * @param string $band
     *
     * @return EventConsumer
     * @throws \OutOfBoundsException
     */
    abstract protected function getConsumer($band);

    protected function getBandName()
    {
        return null;
    }

    protected function getDefaultTimeout()
    {
        return 60;
    }

    protected function configure()
    {
        $this->startTime = time();
        parent::configure();

        $this
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Timeout to wait when no events exist', $this->getDefaultTimeout())
            ->addOption('events', null, InputOption::VALUE_REQUIRED, 'Limit of events to be dispatched', 0)
            ->addOption('max-execution-time', 'm', InputOption::VALUE_REQUIRED, 'Time limit for dispatching')
        ;
        if (!$this->getBandName()) {
            $this->addArgument('band', InputArgument::REQUIRED, 'Name of band');
        }
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $band = $this->getBandName() ?: $input->getArgument('band');
        $this->idleTimeout = $input->getOption('timeout');
        $this->maxExecutionTime = $input->getOption('max-execution-time');

        $consumeTimeout = $this->maxExecutionTime ? min($this->idleTimeout, $this->maxExecutionTime) : $this->idleTimeout;

        $processor = new DispatchProcessor($this->getDispatcher(), $this->getConsumer($band), $band, $consumeTimeout);
        $this->configureControl($input, $processor);

        $processor->process();
    }

    protected function configureControl(InputInterface $input, DispatchProcessor $processor)
    {
        $dispatcher = $this->getDispatcher();

        if ($this->maxExecutionTime) {
            $limiter = new TimeLimiter($this->maxExecutionTime);
            $limiterCallback = function (DispatchStopEvent $event) use ($limiter, $processor) {
                $timeLeft = $limiter->checkLimit($event);
                $processor->setTimeout(min($this->idleTimeout, $timeLeft));
            };
            $dispatcher->subscribe(new CallbackSubscription(DispatchTimeoutEvent::name(), $limiterCallback));
        }

        $events = $input->getOption('events');
        if ($events) {
            $limiter = new EventLimiter($events);
            $limiterCallback = function (DispatchStopEvent $event) use ($limiter) {
                $limiter->checkLimit($event);
            };
            $dispatcher->subscribe(new CallbackSubscription(DispatchStopEvent::name(), $limiterCallback));
        }

        $signalCallback = function (StoppableDispatchEvent $event) {
            if (!$this->isActive()) {
                $event->stopDispatching();
            }
        };
        $dispatcher->subscribe(new CallbackSubscription(DispatchStopEvent::name(), $signalCallback));
        $dispatcher->subscribe(new CallbackSubscription(DispatchTimeoutEvent::name(), $signalCallback));
    }
}