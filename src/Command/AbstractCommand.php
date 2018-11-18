<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Base class for monorepo command.
 *
 * @author Anthonius Munthi <me@itstoni.com>
 */
class AbstractCommand extends Command
{
    protected $dryRun = false;

    /**
     * @return bool
     */
    public function dryRun()
    {
        return $this->dryRun;
    }

    /**
     * @param bool $dryRun
     *
     * @return AbstractCommand
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;

        return $this;
    }
}
