<?php

namespace EventBand\Command;

use EventBand\Adapter\Symfony\SerializableSymfonyEvent;
use EventBand\Transport\EventPublisher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Description of PublishCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class PublishCommand extends AbstractPublishCommand
{
    private $publisher;

    public function setPublisher(EventPublisher $publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getPublisher()
    {
        if (!$this->publisher) {
            throw new \BadMethodCallException('No publisher was set');
        }

        return $this->publisher;
    }

    protected function configure()
    {
        parent::configure();

        $this->addArgument('name', InputArgument::REQUIRED, 'Event name');
    }

    /**
     * {@inheritDoc}
     */
    protected function createEvent(InputInterface $input)
    {
        $event = new SerializableSymfonyEvent();
        $event->setName($input->getArgument('name'));

        return $event;
    }
}
