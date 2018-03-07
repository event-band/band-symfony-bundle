<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
 */

namespace EventBand\Adapter\Symfony\Tests\Command;

use EventBand\Adapter\Symfony\Command\AbstractDispatchCommand;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractDispatchCommandTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class AbstractDispatchCommandTest extends TestCase
{
    /**
     * @var AbstractDispatchCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    private $command;

    protected function setUp()
    {
        $this->command = $this->getMockBuilder('EventBand\Adapter\Symfony\Command\AbstractDispatchCommand')
            ->setMethods([
                'getDispatcher',
                'getConsumer',
                'getBandName',
                'getDefaultTimeout'
            ])
            ->disableOriginalConstructor()
            ->createMock()
        ;
    }

    /**
     * @test if band name is not set argument is added
     */
    public function unsetBandName()
    {
        $this->command->__construct('dispatch');

        $this->assertTrue($this->command->getDefinition()->hasArgument('band'));
    }

    /**
     * @test if band named is set no argument is added
     */
    public function setBandName()
    {
        $this->command
            ->expects($this->any())
            ->method('getBandName')
            ->will($this->returnValue('name'))
        ;

        $this->command->__construct('dispatch');

        $this->assertFalse($this->command->getDefinition()->hasArgument('band'));
    }

}
