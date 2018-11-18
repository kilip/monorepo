<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Fixtures
{
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $target;

    public function __construct($origin)
    {
        $origin = realpath($origin);
        $dirname = str_replace(__DIR__.'/fixtures/', '', $origin);
        $target = sys_get_temp_dir().'/monorepo/'.$dirname;

        $this->origin = $origin;
        $this->target = $target;
        $this->filesystem = new Filesystem();

        $this->cleanDir($target);
        $this->filesystem->mirror($origin, $target);
    }

    public function cleanDir($directory)
    {
        $this->filesystem->remove($directory);
    }

    public function commit($message = 'initial commit', $cwd = null)
    {
        $this->execute("git commit -am '".$message."'", $cwd);
    }

    public function execute($command, $cwd = null)
    {
        $cwd = is_null($cwd) ? $this->target : $cwd;
        if (!is_dir($cwd)) {
            $this->filesystem->mkdir($cwd);
        }
        $process = new Process($command, $cwd);
        $process->run();
    }

    /**
     * @return bool|string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function initGit($cwd = null)
    {
        $this->execute('git init .', $cwd);
        $this->execute('git add .', $cwd);
    }

    public function initSplit($dir)
    {
        $this->cleanDir($dir);
        $this->initGit($dir);
        // always checkout to temp to prevent error
        $this->execute('git checkout -b temp', $dir);
    }

    /**
     * @param bool|string $origin
     *
     * @return Fixtures
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @param string $target
     *
     * @return Fixtures
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }
}
