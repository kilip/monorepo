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

namespace MonorepoTest;

use Monorepo\ApplicationFactory;
use Monorepo\Console\Application;
use PHPUnit\Framework\TestCase;

/**
 * Class ApplicationFactoryTest.
 *
 * @author  Anthonius Munthi <https://itstoni.com>
 * @covers  \Monorepo\ApplicationFactory
 * @covers  \Monorepo\DI\Compiler\DefaultPass
 */
class ApplicationFactoryTest extends TestCase
{
    public function testBoot()
    {
        $app = new ApplicationFactory();
        $app->boot();
        $container = $app->getContainer();

        $this->assertInstanceOf(Application::class, $container->get('monorepo.app'));
    }
}
