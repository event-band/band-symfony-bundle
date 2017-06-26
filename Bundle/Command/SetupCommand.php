<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Command;

use EventBand\Adapter\Symfony\Command\AbstractSetupCommand;
use EventBand\Bundle\DependencyInjection\EventBandExtension;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class SetupCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SetupCommand extends AbstractSetupCommand
{
    const DEFINITION_SEPARATOR = ':';

    private $container;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('event-band:setup')
            ->addArgument('definition', InputArgument::OPTIONAL, 'Definition name', '');
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigurator()
    {
        return $this->getContainer()->get(EventBandExtension::getTransportConfiguratorId());
    }

    protected function getConfigurationDefinition(array $options)
    {
        $definition = $options['definition'];
        $container = $this->getContainer();
        $definitionIds = $container->getParameter('event_band.transport_definitions');
        if (empty($definition)) {
            $definitions = $definitionIds;
        } elseif (strpos($definition, self::DEFINITION_SEPARATOR)) {
            list($type, $definition) = explode(self::DEFINITION_SEPARATOR, $definition, 2);
            if (!isset($definitionIds[$type][$definition])) {
                throw new \OutOfBoundsException(sprintf('No definition "%s" for type "%s" was found', $definition, $type));
            }

            $definitions = $definitionIds[$type][$definition];
        } else {
            if (!isset($definitionIds[$definition])) {
                throw new \OutOfBoundsException('No definition type "%s" was found');
            }

            $definitions = $definitionIds[$definition];
        }

        return $this->resolveDefinition($definitions);
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            /** @var $app \Symfony\Bundle\FrameworkBundle\Console\Application */
            $app = $this->getApplication();
            $this->container = $app->getKernel()->getContainer();
        }

        return $this->container;
    }

    protected function resolveDefinition($definitions)
    {
        if (is_array($definitions)) {
            return array_map([$this, __FUNCTION__], $definitions);
        }

        return $this->getContainer()->get($definitions);
    }
}