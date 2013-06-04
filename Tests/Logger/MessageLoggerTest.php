<?php

namespace EventBand\Bundle\Tests\Logger;

use EventBand\Bundle\Logger\MessageLogger;

class MessageLoggerTest extends \PHPUnit_Framework_TestCase
{

    public function testLogGetsCalledOnPublished()
    {
        $logger = $this->getMock("Psr\\Log\\LoggerInterface");

        $logger->expects($this->once())->method("debug")->with("body", [
            "messageId" => 123,
            "exchange" => "exchange",
            "routingKey" => "routing.key"
        ]);

        $message = $this->getMock("PhpAmqpLib\\Message\\AMQPMessage", ["getBody", "getMessageId"], [], "", false);
        $message->expects($this->once())->method("getBody")->will($this->returnValue("body"));
        $message->expects($this->once())->method("getMessageId")->will($this->returnValue(123));

        $publication = $this->getMock("EventBand\\Transport\\Amqp\\Driver\\MessagePublication", [], [], "", false);
        $publication->expects($this->any())->method("getMessage")->will($this->returnValue($message));

        $messageLogger = new MessageLogger($logger);

        $messageLogger->published($publication, "exchange", "routing.key");
    }

}
