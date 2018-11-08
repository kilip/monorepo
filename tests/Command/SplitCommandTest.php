<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\Dotfiles\Monorepo\Command;

use Dotfiles\Monorepo\Command\AbstractCommand;
use Dotfiles\Monorepo\Command\SplitCommand;
use Dotfiles\Monorepo\Test\CommandTestCase;

class SplitCommandTest extends CommandTestCase
{
    public function testExecute()
    {
        $tester = $this->getCommandTester('split');
        $tester->run(array(
            'command' => 'split',
            '--dry-run',
            '--config' => __DIR__.'/fixtures/test.yaml',
        ));

        $output = $tester->getDisplay();
        $this->assertContains('Executing in dry-run mode', $output);
    }

    public function testGetSplitLiteExecutable()
    {
        $target = new SplitCommand();
        $this->assertFileExists($target->getSplitLiteExecutable());
    }

    public function testInheritance()
    {
        $target = new SplitCommand();
        $this->assertInstanceOf(AbstractCommand::class, $target);
    }
}
