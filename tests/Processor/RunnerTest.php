<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest;

use Monorepo\Console\Logger;
use Monorepo\Processor\Runner;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class RunnerTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Processor\Runner
 */
class RunnerTest extends TestCase
{
    use OutputTrait;

    public function setUp()
    {
        $this->resetOutput();
    }

    public function testRun()
    {
        $output = $this->getOutput();
        $logger = new Logger($output);
        $runner = new Runner($logger);

        $runner->run('php --version');
        $display = $this->getDisplay();
        $this->assertContains('PHP '.PHP_VERSION, $display);
    }
}
