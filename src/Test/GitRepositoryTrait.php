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

namespace Monorepo\Test;

use Gitonomy\Git\Admin;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait GitRepositoryTrait.
 *
 * @author Anthonius Munthi <me@itstoni.com>
 */
trait GitRepositoryTrait
{
    /**
     * @param string $target
     *
     * @return string An empty target remote dir
     */
    public function createEmptyRemote($target)
    {
        $target = $this->getTempDir().'/remote/'.$target;
        $fs     = new Filesystem();

        $fs->remove($target);
        Admin::init($target);

        return $target;
    }

    public function createRemoteFrom($target, $fixturesDir)
    {
        $target  = $this->getTempDir().'/remote/'.$target;
        $tempDir = $this->getTempDir().'/temp/'.$target;
        $fs      = new Filesystem();

        $fs->remove($tempDir);
        $fs->remove($target);

        Admin::init($target);
        $tempRepo = Admin::init($tempDir, false);
        $fs->mirror($fixturesDir, $tempRepo->getWorkingDir());
        $tempRepo->run('add', array('.', '-A'));
        $tempRepo->run('commit', array('-am', '"initial commit"'));
        $tempRepo->run('remote', array('add', 'origin', $target));
        $tempRepo->run('push', array('-u', 'origin', 'master'));

        return $target;
    }

    public function getTempDir()
    {
        return sys_get_temp_dir().'/monorepo';
    }
}
