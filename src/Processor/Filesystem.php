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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

/**
 * Class Filesystem.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 *
 * @method copy($originFile, $targetFile, $overwriteNewerFiles = false)
 * @method mkdir(string $dir, string $mode=0777)
 * @method touch($files, $time = null, $atime = null)
 * @method remove($files)
 * @method chmod($files, $mode, $umask = 0000, $recursive = false)
 * @method chown($files, $user, $recursive = false)
 * @method chgrp($files, $group, $recursive = false)
 * @method rename($origin, $target, $overwrite = false)
 * @method symlink($originDir, $targetDir, $copyOnWindows = false)
 * @method hardlink($originFile, $targetFiles)
 * @method mirror($originDir, $targetDir, \Traversable $iterator = null, $options = array())
 */
class Filesystem
{
    protected $executor;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output   = $output;
        $this->executor = new BaseFilesystem();
    }

    public function __call($name, $arguments)
    {
        $fs     = $this->executor;
        $return = \call_user_func_array([$fs, $name], $arguments);

        $loggedMethods = self::getLoggedMethods();

        if (\in_array($name, $loggedMethods)) {
            $this->log($name, $arguments);
        }

        return $return;
    }

    public static function getLoggedMethods()
    {
        $methods      = get_class_methods(BaseFilesystem::class);
        $methodFilter = ['__construct', '__call', 'exists'];
        $methods      = array_diff($methods, $methodFilter);
        sort($methods);

        return $methods;
    }

    private function log($name, $arguments)
    {
        $output   = $this->output;
        $flag     = sprintf('<bg=white;options=bold;> FSY </> <fg=green;options=bold;>%s </>', $name);
        $format   = '<comment>%s</comment>';
        $subjects = $arguments[0];

        if (!\is_array($subjects)) {
            $subjects = [$subjects];
        }

        switch ($name) {
            case 'mkdir':
                $mode   = $arguments[1] ?? 0777;
                $mode   = decoct($mode & 0777);
                $format = '%s '.sprintf('mode: <info>%s</info>', $mode);

                break;
            case 'copy':
                $format = 'from <comment>%s</comment> to '.sprintf('<comment>%s</comment>', $arguments[1]);

                break;
            case 'touch':
                $time   = $arguments[1] ?? 'n/a';
                $atime  = $arguments[2] ?? 'n/a';
                $format = '<comment>%s</comment> '.sprintf(
                    'time: <comment>%s</comment> atime: <comment>%s</comment>',
                    $time,
                    $atime
                    );

                break;
        }

        foreach ($subjects as $subject) {
            $subject = strtr($subject, [
                getcwd()       => '$CWD',
                getenv('HOME') => '$HOME',
            ]);
            $output->writeln($flag.sprintf($format, $subject), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
    }
}
