<?php

declare(strict_types=1);

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Test;

use Monorepo\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class CommandTestCase extends TestCase
{
    /**
     * @return ApplicationTester
     */
    final public function getCommandTester()
    {
        $app = new Application();
        $app->setAutoExit(false);
        $app->setCatchExceptions(true);
        $tester = new ApplicationTester($app);

        return $tester;
    }
}
