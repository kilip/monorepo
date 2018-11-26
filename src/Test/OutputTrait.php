<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Test;

use Symfony\Component\Console\Output\StreamOutput;

/**
 * Trait OutputTrait.
 *
 * @author      Anthonius Munthi <https://itstoni.com>
 */
trait OutputTrait
{
    /**
     * @var StreamOutput
     */
    protected $output;

    /**
     * Gets the display returned by the last execution of the command or application.
     *
     * @param bool $normalize Whether to normalize end of lines to \n or not
     *
     * @return string The display
     */
    public function getDisplay($normalize = false)
    {
        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());

        if ($normalize) {
            $display = str_replace(PHP_EOL, "\n", $display);
        }

        return $display;
    }

    /**
     * @return StreamOutput
     */
    public function getOutput()
    {
        if (!is_object($this->output)) {
            $stream = fopen('php://memory', 'w', false);
            $this->output = new StreamOutput($stream);
        }

        return $this->output;
    }

    /**
     * Reset output.
     */
    public function resetOutput()
    {
        $this->output = null;
    }
}
