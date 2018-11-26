<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest\Console;

use Monorepo\Console\Logger;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoggerTest.
 *
 * @author      Anthonius Munthi <https://itstoni.com>
 * @covers      \Monorepo\Console\Logger
 */
class LoggerTest extends TestCase
{
    use OutputTrait;

    /**
     * @var Logger
     */
    private $logger;

    public function setUp()
    {
        $this->resetOutput();
        $this->logger = new Logger($this->getOutput());
    }

    public function getTestLog()
    {
        $dateTime = new \DateTime();

        return array(
            array('debug', 'debug message', array(), 'debug message'),
            array('debug', 'debug param {0}', array('foo'), 'debug param foo'),
            array('command', 'command param {0}', array('foo'), array('CMD', 'param foo')),
            array(
                'info',
                'info param {0} object {1}',
                array($dateTime, $this),
                array(
                    'INF',
                    'param '.$dateTime->format('Y-m-d'),
                    '[object '.\get_class($this).']',
                ),
            ),
            array('commandOutput', 'command output', array(), 'command output'),
            array('error', 'command error', array(), 'command error'),
            array('notice', 'notice', array(), array('INF', 'notice')),
        );
    }

    public function testError()
    {
        $outputMock = $this->createMock(ConsoleOutputInterface::class);
        $output = $this->getOutput();
        $logger = new Logger($outputMock);

        $outputMock->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn($output)
        ;

        $logger->error('some error');
        $display = $this->getDisplay();
        $this->assertTrue($logger->hasErrored());
        $this->assertContains('ERR', $display);
    }

    /**
     * @param string       $method
     * @param array|string $expectedMessage
     * @dataProvider          getTestLog
     */
    public function testLog($method, $message, $context, $expectedMessage)
    {
        $this->getOutput()->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

        call_user_func_array(array($this->logger, $method), array($message, $context));

        if (!is_array($expectedMessage)) {
            $expectedMessage = array($expectedMessage);
        }
        $display = $this->getDisplay(true);
        foreach ($expectedMessage as $item) {
            $this->assertContains($item, $display);
        }
    }

    public function testLogWithUndefinedLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The log level "foo" does not exist.');

        $this->logger->log('foo', 'foo message');
    }
}
