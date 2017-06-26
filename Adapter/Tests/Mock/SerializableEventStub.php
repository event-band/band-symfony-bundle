<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony\Tests\Mock;

use EventBand\Adapter\Symfony\SerializableSymfonyEvent;

/**
 * Class SerializeEventStub
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SerializableEventStub extends SerializableSymfonyEvent
{
    private $foo;

    public function __construct($name, $foo)
    {
        $this->setName($name);
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    protected function toSerializableArray()
    {
        return array_merge(parent::toSerializableArray(), ['foo' => $this->foo]);
    }

    protected function fromUnserializedArray(array $data)
    {
        parent::fromUnserializedArray($data);

        if (!isset($data['foo'])) {
            throw new \RuntimeException('Key "foo" is not set in unserialized array');
        }
        $this->foo = $data['foo'];
    }
}