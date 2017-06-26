<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\SectionConfiguration;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class SectionConfigurationTestCase
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
abstract class SectionConfigurationTestCase extends TestCase
{
    private $section;

    /**
     * @return SectionConfiguration
     */
    abstract protected function createSection();

    /**
     * @return SectionConfiguration
     */
    protected function getSection()
    {
        if (!$this->section) {
            $this->section = $this->createSection();
        }

        return $this->section;
    }

    protected function processSection(array $config, $sectionName = true)
    {
        return $this->processNode($this->getSection()->getSectionDefinition(), $config, $sectionName);
    }

    protected function processNode(NodeDefinition $definition, array $config, $sectionName = true)
    {
        $processor = new Processor();

        $node = $definition->getNode(true);
        if ($sectionName) {
            $config = [$node->getName() => $config];
        }

        return $processor->process(
            $node,
            $config
        );
    }

    protected function tearDown()
    {
        $this->section = null;
    }
}