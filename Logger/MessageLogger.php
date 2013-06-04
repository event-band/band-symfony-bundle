<?php

namespace EventBand\Bundle\Logger;

use EventBand\Logger\PublicationLogger;
use EventBand\Transport\Amqp\Driver\MessagePublication;
use Psr\Log\LoggerInterface;

class MessageLogger implements PublicationLogger
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MessageEntry[]
     */
    protected $messages;


    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function published(MessagePublication $publication, $exchange, $routingKey)
    {
        if ($this->logger !== null) {
            $this->logger->debug(
                $publication->getMessage()->getBody(),
                [
                    "messageId" => $publication->getMessage()->getMessageId(),
                    "exchange" => $exchange,
                    "routingKey" => $routingKey
                ]
            );

            $this->messages[] = (new MessageEntry())
                ->setBody($publication->getMessage()->getBody())
                ->setExchange($exchange)
                ->setMessageId($publication->getMessage()->getMessageId())
                ->setRoutingKey($routingKey);
        }
    }

    /**
     * @return MessageEntry[]
     */
    public function getLoggedMessages()
    {
        return $this->messages;
    }

}
