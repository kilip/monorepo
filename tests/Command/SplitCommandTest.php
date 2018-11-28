<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest\Command;

use Monorepo\Command\SplitCommand;
use Monorepo\Event\EventDispatcher;
use Monorepo\Test\CommandTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SplitCommandTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Command\SplitCommand
 */
class SplitCommandTest extends CommandTestCase
{
    public function testRunCommand()
    {
        $logger     = $this->createMock(LoggerInterface::class);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $input      = $this->createMock(InputInterface::class);
        $output     = $this->createMock(OutputInterface::class);

        $logger->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                array('dispatching event {0}', array(SplitCommand::SPLIT_EVENT_PRE)),
                array('dispatching event {0}', array(SplitCommand::SPLIT_EVENT)),
                array('dispatching event {0}', array(SplitCommand::SPLIT_EVENT_POST))
            );

        $dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                array(SplitCommand::SPLIT_EVENT_PRE),
                array(SplitCommand::SPLIT_EVENT),
                array(SplitCommand::SPLIT_EVENT_POST)
            );

        $target = new SplitCommand($logger, $dispatcher);
        $target->run($input, $output);
    }
}
