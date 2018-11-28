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

namespace MonorepoTest\Event;

use Monorepo\Event\ConfigEvent;
use Monorepo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigEventTest extends TestCase
{
    public function testConfigFile()
    {
        $event = new ConfigEvent();
        $event->setConfigFile(__FILE__);
        $this->assertEquals(__FILE__, $event->getConfigFile());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The config file "foo" not exists or not readable.');

        $event->setConfigFile('foo');
    }
}
