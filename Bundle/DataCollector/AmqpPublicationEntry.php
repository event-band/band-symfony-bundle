<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DataCollector;

use EventBand\Transport\Amqp\Driver\MessagePublication;

/**
 * Entry about message publication
 *
 * @author Nikita Nefedov <inefedor@gmail.com>
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class AmqpPublicationEntry
{
    private $publication;
    private $exchange;
    private $routingKey;

    public function __construct(MessagePublication $publication, $exchange, $routingKey = '')
    {
        $this->publication = $publication;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    /**
     * @return MessagePublication
     */
    public function getPublication()
    {
        return $this->publication;
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
