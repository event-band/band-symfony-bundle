<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace EventBand\Bundle\DependencyInjection;

use EventBand\Bundle\DependencyInjection\Compiler\SerializerPass;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
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
     * @var Loader\XmlFileLoader
     */
    private $loader;

    /**
     * {@inheritDoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $this->loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $this->loader->load('services.xml');

        foreach ($mergedConfig['transports'] as $name => $transportConfig) {
            $this->{'load'.ucfirst($name).'Transport'}($transportConfig, $container);
        }

        $this->loadSerializers($mergedConfig['serializers'], $container);
        $this->loadRouters($mergedConfig['routers'], $container);
        $this->loadPublishers($mergedConfig['publishers'], $container);
        $this->loadConsumers($mergedConfig['consumers'], $container);
    }

    private function loadAmqpTransport(array $config, ContainerBuilder $container)
    {
        $this->loader->load('transport/amqp/amqp.xml');
        $this->loader->load(sprintf('transport/amqp/%s.xml', $config['driver']));

        if (class_exists('JMS\AopBundle\JMSAopBundle')) {
            $this->loader->load('transport/amqp/tracer.xml');
        }

        $camelizeKey = function (array $config) use (&$camelizeKey) {
            $camelized = [];
            foreach ($config as $key => $value) {
                $camelized[lcfirst(ContainerBuilder::camelize($key))] = $value;
            }

            return $camelized;
        };

        $camelizeKeyRecursive = function (array $config) use (&$camelizeKey) {
            $camelized = [];
            foreach ($config as $key => $value) {
                if (is_array($value)) {
                    $camelized[lcfirst(ContainerBuilder::camelize($key))] = $camelizeKey($value);
                } else {
                    $camelized[lcfirst(ContainerBuilder::camelize($key))] = $value;
                }
            }

            return $camelized;
        };

        $definitions = [];
        foreach ($config['connections'] as $name => $connectionConfig) {
            $exchanges = $connectionConfig['exchanges'];
            unset($connectionConfig['exchanges']);
            $queues = $connectionConfig['queues'];
            unset($connectionConfig['queues']);
            $servers = $connectionConfig['connection']['servers'];
            unset($connectionConfig['connection']['servers']);

            $amqp = new DefinitionDecorator('event_band.transport.amqp.definition');
            $amqp->addMethodCall('connections', [$camelizeKeyRecursive($servers)]);
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

            $driverPoolStrategy = new Definition('EventBand\Transport\AmqpLib\Pool\Strategy\StrategyInterface');
            $driverPoolStrategy->setPublic(false);
            $driverPoolStrategy->addArgument($connectionConfig['connection']['strategy']);
            $driverPoolStrategy->setFactory([new Reference(sprintf('event_band.transport.amqp.driver.driver_pool.strategy_factory.%s', $config['driver'])), 'create']);

            $driverPool = new DefinitionDecorator(sprintf('event_band.transport.amqp.driver.driver_pool.%s', $config['driver']));
            $driverPool->replaceArgument(0, $driverPoolStrategy);

            $container->setDefinition(self::getAmqpDriverPoolId($name), $driverPool);

            foreach ($servers as $index => $server) {
                $connection = new Definition('EventBand\Transport\Amqp\Definition\ConnectionDefinition');
                $connection->setPublic(false);
                $connection->addArgument($index);
                $connection->setFactory([new Reference($definitionId), 'getConnection']);

                $container->setDefinition(self::getAmqpConnectionDefinitionId($name, $index), $connection);

                $factory = new DefinitionDecorator(sprintf('event_band.transport.amqp.connection_factory.%s', $config['driver']));
                $factory->addMethodCall('setDefinition', [new Reference(self::getAmqpConnectionDefinitionId($name, $index))]);
                $container->setDefinition(self::getAmqpLibConnectionFactoryId($name, $index), $factory);

                $parent = $container->getDefinition('event_band.transport.amqp.driver.'.$config['driver']);

                $driver = new DefinitionDecorator('event_band.transport.amqp.driver.'.$config['driver']);
                $driver->setClass($parent->getClass());
                $driver->replaceArgument(0, new Reference(self::getAmqpLibConnectionFactoryId($name, $index)));
                $container->setDefinition(self::getAmqpDriverId($name, $index), $driver);

                $driverPool->addMethodCall('addDriver', [$driver]);
            }

            $container->setAlias(self::getAmqpDriverId($name), new Alias(self::getAmqpDriverPoolId($name)));

            $configurator = new DefinitionDecorator('event_band.transport.amqp.configurator');
            $configurator->replaceArgument(0, new Reference(self::getAmqpDriverId($name)));
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
            $definition->replaceArgument(0, new Reference(self::getSerializerId($converterConfig['parameters']['serializer'])));

            $container->setDefinition(self::getAmqpConverterId($name), $definition);
        }
    }

    private function loadSerializers(array $config, ContainerBuilder $container)
    {
        $loadedAdapters = [
            'native' => true
        ];
        foreach ($config as $name => $serializerConfig) {
            $adapter = $serializerConfig['type'];
            $parameters = $serializerConfig['parameters'];

            if (!isset ($loadedAdapters[$adapter])) {
                $this->loader->load(sprintf('serializer/%s.xml', $adapter));
            }

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
                    $router->replaceArgument(0, $parameters);
                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unknown router type "%s"', $type));
            }

            $container->setDefinition(self::getRouterId($name), $router);
        }
    }

    private function loadPublishers(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $publisherConfig) {
            $transport = $publisherConfig['transport']['type'];
            $parameters = $publisherConfig['transport']['parameters'];

            switch ($transport) {
                case 'amqp':
                    $publisher = new DefinitionDecorator('event_band.transport.amqp.publisher');
                    $publisher
                        ->replaceArgument(0, new Reference(self::getAmqpDriverId($parameters['connection'])))
                        ->replaceArgument(1, new Reference(self::getAmqpConverterId($parameters['converter'])))
                        ->replaceArgument(2, $parameters['exchange'])
                        ->replaceArgument(3, new Reference(self::getRouterId($parameters['router'])))
                        ->replaceArgument(4, $parameters['persistent'])
                        ->replaceArgument(5, $parameters['mandatory'])
                        ->replaceArgument(6, $parameters['immediate'])
                    ;
                    $container->setDefinition(self::getPublisherId($name), $publisher);
                    break;

                default:
                    throw new \Exception('Not implemented');
            }

            $listener = new DefinitionDecorator('event_band.publish_listener');
            $listener
                ->replaceArgument(0, new Reference(self::getPublisherId($name)))
                ->replaceArgument(1, $publisherConfig['propagation'])
            ;

            foreach ($publisherConfig['events'] as $event) {
                $listener->addTag('event_band.subscription', [
                    'event' => $event,
                    'priority' => $publisherConfig['priority']
                ]);
            }

            $container->setDefinition(self::getListenerId($name), $listener);
        }
    }

    private function loadConsumers(array $config, ContainerBuilder $container)
    {
        foreach ($config as $name => $dispatcherConfig) {
            $transport = $dispatcherConfig['transport']['type'];
            $parameters = $dispatcherConfig['transport']['parameters'];
            switch ($transport) {
                case 'amqp':
                    $reader = new DefinitionDecorator('event_band.transport.amqp.consumer');
                    $reader
                        ->replaceArgument(0, new Reference(self::getAmqpDriverId($parameters['connection'])))
                        ->replaceArgument(1, new Reference(self::getAmqpConverterId($parameters['converter'])))
                        ->replaceArgument(2, $parameters['queue'])
                    ;
                    $container->setDefinition(self::getConsumerId($name), $reader);
                    break;

                default:
                    throw new \Exception('Not implemented');
                    break;
            }
        }
    }



    /**
     * @param string $name
     *
     * @return string
     */
    public static function getPublisherId($name)
    {
        return sprintf('event_band.publisher.%s', $name);
    }

    public static function getListenerId($name)
    {
        return sprintf('event_band.listener.%s', $name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function getSerializerId($name)
    {
        return sprintf('event_band.serializer.%s', $name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function getRouterId($name)
    {
        return sprintf('event_band.router.%s', $name);
    }

    public static function getConsumerId($name)
    {
        return sprintf('event_band.consumer.%s', $name);
    }

    public static function getTransportDefinitionId($type, $name)
    {
        return sprintf('event_band.transport.%s.definition.%s', $type, $name);
    }

    private static function getAmqpConnectionDefinitionId($name, $index)
    {
        return sprintf('event_band.transport.amqp.connection_definition.%s.%d', $name, $index);
    }

    public static function getAmqpDriverPoolId($connectionName)
    {
        return sprintf('event_band.transport.amqp.driver_pool.%s', $connectionName);
    }

    public static function getAmqpDriverId($connectionName, $index = null)
    {
        if ($index !== null) {
            return sprintf('event_band.transport.amqp.connection_driver.%s.%d', $connectionName, $index);
        }

        return sprintf('event_band.transport.amqp.connection_driver.%s', $connectionName);
    }

    public static function getAmqpConverterId($name)
    {
        return sprintf('event_band.transport.amqp.converter.%s', $name);
    }

    private static function getAmqpLibConnectionFactoryId($name, $index)
    {
        return sprintf('event_band.transport.amqplib.connection_factory.%s.%d', $name, $index);
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
