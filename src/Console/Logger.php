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

use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Logger extends AbstractLogger
{
    const CMD        = 'cmd';
    const ERROR      = 'info';
    const INFO       = 'info';
    const OUT        = 'out';

    private $errored        = false;
    private $formatLevelMap = [
        LogLevel::EMERGENCY => self::INFO,
        LogLevel::ALERT     => self::INFO,
        LogLevel::CRITICAL  => self::INFO,
        LogLevel::ERROR     => self::ERROR,
        LogLevel::WARNING   => self::INFO,
        LogLevel::NOTICE    => self::INFO,
        LogLevel::INFO      => self::INFO,
        LogLevel::DEBUG     => self::INFO,
        LogLevel::CMD       => self::INFO,
        LogLevel::OUT       => self::INFO,
    ];

    private $output;

    private $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING   => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE    => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::INFO      => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::DEBUG     => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::CMD       => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::OUT       => OutputInterface::VERBOSITY_NORMAL,
    ];

    public function __construct(OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        $this->output            = $output;
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap    = $formatLevelMap + $this->formatLevelMap;
    }

    public function command($message, array $context = [])
    {
        $this->log(LogLevel::CMD, $message, $context);
    }

    public function commandOutput($message, array $context = [])
    {
        $this->log(LogLevel::OUT, $message, $context);
    }

    /**
     * Returns true when any messages have been logged at error levels.
     *
     * @return bool
     */
    public function hasErrored()
    {
        return $this->errored;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new \InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $output = $this->output;

        // Write to the error output if necessary and available
        if (self::ERROR === $this->formatLevelMap[$level]) {
            if ($this->output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }
            $this->errored = true;
        }

        // the if condition check isn't necessary -- it's the same one that $output will do internally anyway.
        // We only do it for efficiency here as the message formatting is relatively expensive.
        if ($output->getVerbosity() >= $this->verbosityLevelMap[$level]) {
            $output->writeln(
                sprintf(
                    '%1$s <%2$s>%3$s</%2$s>',
                    $this->getLevelFormat($level),
                    $this->formatLevelMap[$level],
                    $this->interpolate($message, $context)
                )
            );
        }
    }

    /**
     * @param string $level
     *
     * @return string formatted map
     */
    private function getLevelFormat($level)
    {
        $colorMap = [
            'info'  => 'white',
            'cmd'   => 'yellow',
            'error' => 'red',
            'out'   => 'yellow',
            'WRN'   => 'yellow',
            'DBG'   => 'white',
        ];

        $levelMap = [
            'warning' => 'WRN',
            'debug'   => 'DBG',
        ];

        if (isset($levelMap[$level])) {
            $level = $levelMap[$level];
        }

        if (!isset($colorMap[$level])) {
            $level = 'info';
        }
        $color = $colorMap[$level];

        $format = sprintf(
            '<bg=%1$s;options=bold;> %2$.3s </>',
            $color,
            strtoupper($level)
        );

        return $format;
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     */
    private function interpolate(string $message, array $context): string
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object '.\get_class($val).']';
            } else {
                $replacements["{{$key}}"] = '['.\gettype($val).']';
            }
        }

        foreach ($replacements as $key => $replacement) {
            $replacements[$key] = '<fg=yellow;options=bold;>'.$replacement.'</>';
        }
        $message = strtr($message, $replacements);
        //$message = str_replace(getcwd(), '.', $message);
        $message = strtr($message, [
            getcwd()       => '$CWD',
            getenv('HOME') => '$HOME',
        ]);

        return $message;
    }
}
