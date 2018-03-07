<?php

namespace EventBand\Bundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * DataCollector for AMQP transport
 *
 * @author Nikita Nefedov <inefedor@gmail.com>
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class AmqpDataCollector extends DataCollector
{
    private $logger;

    public function __construct(PublicationTracer $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'publications' => $this->logger->getLoggedPublications()
        ];
    }

    public function getPublications()
    {
        return $this->data['publications'];
    }

    public function getPublicationCount()
    {
        return count($this->data['publications']);
    }

    public function getName()
    {
        return "event_band_amqp";
    }

    public function reset()
    {
        $this->data = [];
        $this->logger->reset();
    }
}
