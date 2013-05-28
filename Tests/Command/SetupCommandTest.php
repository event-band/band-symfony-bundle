<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\Tests\Command;

use EventBand\Bundle\Command\SetupCommand;
use EventBand\Bundle\DependencyInjection\EventBandExtension;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class SetupCommandTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SetupCommandTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;
    private $application;
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * Setup application with command
     */
    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container))
        ;
        $this->application = new Application($kernel);
        $this->application->add(new SetupCommand());
        $this->tester = new CommandTester($this->application->get('event-band:setup'));
    }

    /**
     * @test definition parameter format ""|"type"|"type:name" will find definitions and pass to configurator
     * @dataProvider definitions
     */
    public function definitionConfigurator($name, $definitions)
    {
        $configurator = $this->getMock('EventBand\Transport\TransportConfigurator');
        $configurator
            ->expects($this->once())
            ->method('setUpDefinition')
            ->with($definitions)
        ;

        $this->container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($id) use ($configurator) {
                if ($id === EventBandExtension::getTransportConfiguratorId()) {
                    return $configurator;
                }
                return 'object_'.$id;
            }))
        ;
        $this->container
            ->expects($this->once())
            ->method('getParameter')
            ->with('event_band.transport_definitions')
            ->will($this->returnValue(['test' => ['name1' => 'id1', 'name2' => 'id2']]))
        ;

        $this->tester->execute(['command' => 'event-band:setup', 'definition' => $name]);
    }

    public function definitions()
    {
        return [
            ['', ['test' => ['name1' => 'object_id1', 'name2' => 'object_id2']]],
            ['test', ['name1' => 'object_id1', 'name2' => 'object_id2']],
            ['test:name2', 'object_id2']
        ];
    }
}
