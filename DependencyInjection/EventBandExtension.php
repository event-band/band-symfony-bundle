<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * EventBandBundle extension class
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class EventBandExtension extends ConfigurableExtension
{
    /**
     * {@inheritDoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->loadSerializers($mergedConfig['serializers'], $container);
        $this->loadRouters($mergedConfig['routers'], $container);


        if (isset($mergedConfig['transports']['amqp'])) {
            $loader->load('amqp/amqp.xml');
            $loader->load('amqp/'.$mergedConfig['transports']['amqp']['driver'].'.xml');
            $this->loadAmqp($mergedConfig['transports']['amqp'], $container);
        }

        $this->loadPublishers($mergedConfig['publishers'], $container);
        $this->loadConsumers($mergedConfig['consumers'], $container);
    }

    private function loadSerializers(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $serializerConfig) {
            $adapter = $serializerConfig['adapter'];
            $parameters = $serializerConfig['parameters'];

            $serializer = new DefinitionDecorator(sprintf('event_band.serializer.adapter.%s', $adapter));

            switch ($adapter) {
                case 'jms':
                    $serializer->replaceArgument(1, $parameters['format']);
                    break;

                case 'native':
                    // No configuration
                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unknown serializer adapter "%s"', $adapter));
            }

            $container->setDefinition($this->getSerializerId($name), $serializer);
        }
    }

    private function loadRouters(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $routerConfig) {
            $type = $routerConfig['type'];
            $parameters = $routerConfig['parameters'];

            $router = new DefinitionDecorator(sprintf('event_band.router.type.%s', $type));

            switch ($type) {
                case 'pattern':
                    $router->replaceArgument(0, $parameters['pattern']);
                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unknown router type "%s"', $type));
            }

            $container->setDefinition($this->getRouterId($name), $router);
        }
    }

    private function loadPublishers(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $publisherConfig) {
            $adapter = $publisherConfig['adapter'];
            $parameters = $publisherConfig['parameters'];

            switch ($adapter) {
                case 'amqp':
                    $publisher = new DefinitionDecorator('event_band.transport.amqp.publisher');
                    $publisher
                        ->replaceArgument(0, new Reference($this->getAmqpDriverId($parameters['connection'])))
                        ->replaceArgument(1, new Reference($this->getAmqpConverterId($parameters['converter'])))
                        ->replaceArgument(2, $parameters['exchange'])
                        ->replaceArgument(3, new Reference($this->getRouterId($parameters['router'])))
                        ->replaceArgument(4, $parameters['persistent'])
                        ->replaceArgument(5, $parameters['mandatory'])
                        ->replaceArgument(6, $parameters['immediate'])
                    ;
                    $container->setDefinition($this->getPublisherId($name), $publisher);
                    break;

                default:
                    throw new \Exception('Not implemented');
                    break;
            }

            $listener = new DefinitionDecorator('event_band.publish_listener');
            $listener
                ->replaceArgument(0, new Reference($this->getPublisherId($name)))
                ->replaceArgument(1, $publisherConfig['propagation'])
            ;

            foreach ($publisherConfig['events'] as $event) {
                $listener->addTag('event_band.subscription', [
                    'event' => $event,
                    'priority' => $publisherConfig['priority']
                ]);
            }

            $container->setDefinition(sprintf('event_band.listener.%s', $name), $listener);
        }
    }

    private function loadConsumers(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $dispatcherConfig) {
            $adapter = $dispatcherConfig['adapter'];
            $parameters = $dispatcherConfig['parameters'];
            switch ($adapter) {
                case 'amqp':
                    $reader = new DefinitionDecorator('event_band.transport.amqp.consumer');
                    $reader
                        ->replaceArgument(0, new Reference($this->getAmqpDriverId($parameters['connection'])))
                        ->replaceArgument(1, new Reference($this->getAmqpConverterId($parameters['converter'])))
                        ->replaceArgument(2, $parameters['queue'])
                    ;
                    $container->setDefinition($this->getConsumerId($name), $reader);
                    break;

                default:
                    throw new \Exception('Not implemented');
                    break;
            }
        }
    }

    private function loadAmqp(array $config, ContainerBuilder $container)
    {
        foreach ($config['connections'] as $name => $connectionConfig) {
            $amqp = new DefinitionDecorator('event_band.transport.amqp.definition');

            $amqpId = $this->getAmqpDefinitionId($name);
            $container->setDefinition($amqpId, $amqp);

            $connection = new DefinitionDecorator('event_band.transport.amqp.connection_definition');
            $connection->setFactoryService($amqpId);
            foreach ($connectionConfig as $key => $value) {
                $connection->addMethodCall(lcfirst(ContainerBuilder::camelize($key)), array($value));
            }

            $connectionId = $this->getAmqpConnectionDefinitionId($name);
            $container->setDefinition($connectionId, $connection);

            $factory = new DefinitionDecorator(sprintf('event_band.transport.amqp.connection_factory.%s', $config['driver']));
            $factory->addMethodCall('setDefinition', [new Reference($connectionId)]);
            $container->setDefinition($this->getAmqpLibConnectionFactoryId($name), $factory);

            $driver = new DefinitionDecorator('event_band.transport.amqp.driver.'.$config['driver']);
            $driver->replaceArgument(0, new Reference($this->getAmqpLibConnectionFactoryId($name)));
            $container->setDefinition($this->getAmqpDriverId($name), $driver);
        }

        foreach ($config['converters'] as $name => $converterConfig) {
            $definition = new DefinitionDecorator('event_band.transport.amqp.converter.serialize');
            $definition->replaceArgument(0, new Reference($this->getSerializerId('default')));

            $container->setDefinition($this->getAmqpConverterId($name), $definition);
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getPublisherId($name)
    {
        return sprintf('event_band.publisher.%s', $name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getSerializerId($name)
    {
        return sprintf('event_band.serializer.%s', $name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getRouterId($name)
    {
        return sprintf('event_band.router.%s', $name);
    }

    private function getConsumerId($name)
    {
        return sprintf('event_band.consumer.%s', $name);
    }

    private function getAmqpDefinitionId($name)
    {
        return sprintf('event_band.transport.amqp.definition.%s', $name);
    }

    private function getAmqpConnectionDefinitionId($name)
    {
        return sprintf('event_band.transport.amqp.connection_definition.%s', $name);
    }

    private function getAmqpDriverId($connectionName)
    {
        return sprintf('event_band.transport.amqp.connection_driver.%s', $connectionName);
    }

    private function getAmqpConverterId($name)
    {
        return sprintf('event_band.transport.amqp.converter.%s', $name);
    }

    private function getAmqpLibConnectionFactoryId($name)
    {
        return sprintf('event_band.transport.amqplib.connection_factory.%s', $name);
    }
}
