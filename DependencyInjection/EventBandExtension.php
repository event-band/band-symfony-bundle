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
                        ->replaceArgument(7, new Reference("event_band.logger"))
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
        $camelizeKey = function (array $config) {
            $camelized = [];
            foreach ($config as $key => $value) {
                $camelized[lcfirst(ContainerBuilder::camelize($key))] = $value;
            }

            return $camelized;
        };

        $definitions = [];
        foreach ($config['connections'] as $name => $connectionConfig) {
            $exchanges = $connectionConfig['exchanges'];
            unset($connectionConfig['exchanges']);
            $queues = $connectionConfig['queues'];
            unset($connectionConfig['queues']);

            $amqp = new DefinitionDecorator('event_band.transport.amqp.definition');
            $amqp->addMethodCall('connection', [$camelizeKey($connectionConfig)]);
            foreach ($exchanges as $exchange => $exchangeConfig) {
                $exchangeType = $exchangeConfig['type'];
                unset($exchangeConfig['type']);
                $amqp->addMethodCall($exchangeType.'Exchange', [$exchange, $camelizeKey($exchangeConfig)]);
            }
            foreach ($queues as $queue => $queueConfig) {
                $amqp->addMethodCall('queue', [$queue, $camelizeKey($queueConfig)]);
            }

            $definitionId = self::getTransportDefinitionId('amqp', $name);
            $container->setDefinition($definitionId, $amqp);
            $definitions[$name] = $definitionId;

            $connection = new DefinitionDecorator('event_band.transport.amqp.connection_definition');
            $connection->setFactoryService($definitionId);
            $connectionId = $this->getAmqpConnectionDefinitionId($name);
            $container->setDefinition($connectionId, $connection);

            $factory = new DefinitionDecorator(sprintf('event_band.transport.amqp.connection_factory.%s', $config['driver']));
            $factory->addMethodCall('setDefinition', [new Reference($connectionId)]);
            $container->setDefinition($this->getAmqpLibConnectionFactoryId($name), $factory);

            $driver = new DefinitionDecorator('event_band.transport.amqp.driver.'.$config['driver']);
            $driver->replaceArgument(0, new Reference($this->getAmqpLibConnectionFactoryId($name)));
            $container->setDefinition($this->getAmqpDriverId($name), $driver);

            $configurator = new DefinitionDecorator('event_band.transport.amqp.configurator');
            $configurator->replaceArgument(0, new Reference($this->getAmqpDriverId($name)));
            $container->setDefinition(self::getTypedTransportConfiguratorId('amqp', $name), $configurator);
            $container->getDefinition(self::getTransportConfiguratorId())
                ->addMethodCall('registerConfigurator', ['amqp.'.$name, new Reference(self::getTypedTransportConfiguratorId('amqp', $name))]);
        }

        $container->setParameter('event_band.transport_definitions', array_merge(
            $container->getParameter('event_band.transport_definitions'),
            ['amqp' => $definitions]
        ));

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

    public static function getTransportDefinitionId($type, $name)
    {
        return sprintf('event_band.transport.%s.definition.%s', $type, $name);
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

    public static function getTransportConfiguratorId()
    {
        return 'event_band.transport_configurator';
    }

    public static function getTypedTransportConfiguratorId($type, $name)
    {
        return sprintf('event_band.transport.%s.configurator.%s', $type, $name);
    }
}
