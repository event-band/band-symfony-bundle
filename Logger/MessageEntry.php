<?php

namespace EventBand\Bundle\Logger;


class MessageEntry
{

    /**
     * @var string
     */
    protected $body;

    /**
     * @var int|null
     */
    protected $messageId;

    /**
     * @var string
     */
    protected $exchange;

    /**
     * @var string
     */
    protected $routingKey;


    /**
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param $messageId
     * @return $this
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @param $exchange
     * @return $this
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;

        return $this;
    }

    /**
     * @param $routingKey
     * @return $this
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return int|null
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

}
