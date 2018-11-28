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

namespace Monorepo\Processor;

use Monorepo\Console\Logger;
use Symfony\Component\Process\Process;

class Runner
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Runs a Process with logged output.
     *
     * @param string      $command
     * @param string|null $cwd
     * @param array|null  $env
     * @param null        $input
     * @param float       $timeout
     *
     * @return $this
     */
    public function run($command, string $cwd = null, array $env = null, $input = null, float $timeout = 0)
    {
        $process = new Process($command, $cwd, $env, $input, $timeout);
        $logger  = $this->logger;
        $cwd     = $process->getWorkingDirectory();

        $logger->command('{0} cwd: {1}', [$command, $cwd]);

        // @codeCoverageIgnoreStart
        $process->run(function ($type, $buffer) use ($logger) {
            $method = 'commandOutput';
            if (Process::ERR === $type) {
                $method = 'error';
            }

            $exp = explode(PHP_EOL, $buffer);
            foreach ($exp as $item) {
                if ('' !== trim($item)) {
                    \call_user_func([$logger, $method], $item);
                }
            }
        });
        // @codeCoverageIgnoreEnd

        return $this;
    }
}
