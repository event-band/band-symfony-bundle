<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony;

use EventBand\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Class SeriazlizableEvent
 *
 * @author     Kirill chEbba Chebunin <iam@chebba.org>
 * @maintainer Vasil coylOne Kulakov <iam@vasiliy.pro>
 */
class SerializableSymfonyEvent extends SymfonyEvent implements Event, \Serializable
{
    /**
     * Name of the event. Used for async events dispatching.
     * Since the name property of symfony events is deprecated, we moved it here.
     * It will not break any symfony concept, because will be set under the hood of symfony adapter.
     * No need to set it from application logic
     *
     * @var string
     */
    protected $name;

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize($this->toSerializableArray());
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Unserialized data is not an array but "%s"', $data));
        }

        $this->fromUnserializedArray($data);
    }

    /**
     * @return array
     */
    protected function toSerializableArray()
    {
        return [
            'name' => $this->getName()
        ];
    }

    /**
     * @param array $data
     */
    protected function fromUnserializedArray(array $data)
    {
        if (!isset($data['name'])) {
            throw new \RuntimeException('Key "name" is not set in unserialized array');
        }

        $this->setName($data['name']);
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

}