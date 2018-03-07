<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Bundle\Tests\DependencyInjection;

use EventBand\Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Setup configuration
     */
    protected function setUp()
    {
        $this->config = new Configuration();
    }

    /**
     * @test default sections are added
     */
    public function defaultSections()
    {
        $config = $this->processConfiguration(['transports' => ['amqp' => []]]);
        $this->assertArrayHasKey('transports', $config);
        $this->assertArrayHasKey('amqp', $config['transports']);
        $this->assertNotEmpty($config['transports']['amqp']);
        $this->assertArrayHasKey('serializers', $config);
        $this->assertArrayHasKey('routers', $config);
        $this->assertArrayHasKey('publishers', $config);
        $this->assertArrayHasKey('consumers', $config);
    }

    private function processConfiguration(array $config)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this->config, ['event_band' => $config]);
    }
}
