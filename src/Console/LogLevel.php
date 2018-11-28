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

namespace Monorepo\Console;

use Psr\Log\LogLevel as BaseLogLevel;

/**
 * Class LogLevel.
 *
 * @author  Anthonius Munthi <https://itstoni.com>
 * @codeCoverageIgnore
 */
class LogLevel extends BaseLogLevel
{
    const CMD = 'cmd';
    const OUT = 'out';
}
