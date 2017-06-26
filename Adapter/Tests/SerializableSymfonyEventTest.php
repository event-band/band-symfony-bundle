<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony\Tests;

use EventBand\Adapter\Symfony\Tests\Mock\SerializableEventStub;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class SerializableSymfonyEventTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SerializableSymfonyEventTest extends TestCase
{
    /**
     * @test serialize()/unserialize() uses toSerializableArray()/fromUnserializedArray()
     */
    public function serializeWithArray()
    {
        $event = new SerializableEventStub('event.name', 'data');

        $serialized = serialize($event);

        $this->assertEquals('C:58:"EventBand\Adapter\Symfony\Tests\Mock\SerializableEventStub":56:{a:2:{s:4:"name";s:10:"event.name";s:3:"foo";s:4:"data";}}', $serialized);

        $unserialized = unserialize($serialized);

        $this->assertEquals('event.name', $unserialized->getName());
        $this->assertEquals('data', $unserialized->getFoo());
    }
}
