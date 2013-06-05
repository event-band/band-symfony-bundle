<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DataCollector;

use CG\Proxy\MethodInterceptorInterface;
use CG\Proxy\MethodInvocation;
use JMS\AopBundle\Aop\PointcutInterface;

/**
 * Class PublicationStack
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class PublicationInterceptor implements PublicationTracer, MethodInterceptorInterface, PointcutInterface
{
    private $entries = [];

    /**
     * Determines whether the advice applies to instances of the given class.
     *
     * There are some limits as to what you can do in this method. Namely, you may
     * only base your decision on resources that are part of the ContainerBuilder.
     * Specifically, you may not use any data in the class itself, such as
     * annotations.
     *
     * @param \ReflectionClass $class
     * @return boolean
     */
    public function matchesClass(\ReflectionClass $class)
    {
        return $class->implementsInterface('EventBand\Transport\Amqp\Driver\AmqpDriver');
    }

    /**
     * Determines whether the advice applies to the given method.
     *
     * This method is not limited in the way the matchesClass method is. It may
     * use information in the associated class to make its decision.
     *
     * @param \ReflectionMethod $method
     * @return boolean
     */
    public function matchesMethod(\ReflectionMethod $method)
    {
        return $method->getName() === 'publish';
    }

    /**
     * {@inheritDoc}
     */
    public function intercept(MethodInvocation $invocation)
    {
        $this->entries[] = new AmqpPublicationEntry(
            $invocation->arguments[0],
            $invocation->arguments[1],
            $invocation->arguments[2]
        );

        return $invocation->proceed();
    }

    /**
     * @return AmqpPublicationEntry[]
     */
    public function getLoggedPublications()
    {
        return $this->entries;
    }
}