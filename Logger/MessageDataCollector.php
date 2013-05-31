<?php

namespace EventBand\Bundle\Logger;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class MessageDataCollector extends DataCollector
{

    /**
     * @var MessageLogger
     */
    protected $logger;

    public function __construct(MessageLogger $logger)
    {
        $this->logger = $logger;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            "messages" => $this->logger->getLoggedMessages()
        ];
    }

    public function getMessages()
    {
        return $this->data["messages"];
    }

    public function getMessagesCount()
    {
        return count($this->data["messages"]);
    }

    public function getName()
    {
        return "event_band";
    }

}
