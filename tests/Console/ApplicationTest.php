<?php

declare(strict_types=1);

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest\Console;

use Monorepo\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ApplicationTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Console\Application
 */
class ApplicationTest extends TestCase
{
    public function testConstruct()
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $app    = new Application($input, $output);

        $this->assertTrue($app->getDefinition()->hasOption('config'));
        $this->assertTrue($app->getDefinition()->hasOption('dry-run'));
    }
}
