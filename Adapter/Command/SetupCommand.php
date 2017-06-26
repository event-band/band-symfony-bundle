<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Adapter\Symfony\Command;

use EventBand\Transport\TransportConfigurator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class SetupCommand
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SetupCommand extends AbstractSetupCommand
{
    private $configurator;
    private $configurationDefinitions = [];

    protected function configure()
    {
        $this
            ->setName('setup')
            ->addArgument('name', InputArgument::OPTIONAL)
        ;
        parent::configure();
    }

    public function setConfigurator(TransportConfigurator $configurator)
    {
        $this->configurator = $configurator;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurator()
    {
        if (!$this->configurator) {
            throw new \BadMethodCallException('Configurator should be set first');
        }

        return $this->configurator;
    }

    public function setConfigurationDefinitions(array $definitions)
    {
        $this->configurationDefinitions = $definitions;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationDefinition(array $options)
    {
        if (!isset($this->configurationDefinitions[$options['name']])) {
            throw new \OutOfBoundsException(sprintf('Unknown definition name "%s"', $options['name']));
        }

        return $options['name'];
    }
}
