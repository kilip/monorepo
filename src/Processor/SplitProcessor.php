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

use Gitonomy\Git\Admin;
use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Repository;
use Monorepo\Command\SplitCommand;
use Monorepo\Config\Config;
use Monorepo\Config\Project;
use Monorepo\Console\Logger;
use Monorepo\Exception\CommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\ExecutableFinder;

class SplitProcessor implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var string
     */
    private $splitsh;

    public function __construct(
        Logger $logger,
        InputInterface $input,
        Runner $runner,
        Config $config
    ) {
        $this->logger = $logger;
        $this->input  = $input;
        $this->runner = $runner;
        $this->config = $config;
        $this->configure();
    }

    public static function getSubscribedEvents()
    {
        return array(
            SplitCommand::SPLIT_EVENT => 'onSplit',
        );
    }

    public function onSplit()
    {
        $splitsh = $this->splitsh;
        $runner  = $this->runner;
        $config  = $this->config;

        foreach ($config->getProjects() as $project) {
            $this->processProject($project);
        }
    }

    /**
     * @throws CommandException when Splitsh executable is not found
     */
    private function configure()
    {
        $dirs = array(
            // in development vendor dir
            __DIR__.'/../../vendor/bin',

            // in vendor mode bin dir
            __DIR__.'/../../../bin',

            // in library bin dir,
            __DIR__.'/../../../../bin',
        );
        $execFinder = new ExecutableFinder();
        $file       = $execFinder->find('splitsh', null, $dirs);

        if (!is_file($file)) {
            throw new CommandException(
                sprintf('Can\'t find "splitsh" executable file.')
            );
        }
        $this->splitsh = realpath($file);
    }

    private function processProject(Project $project)
    {
        $this->logger->info('processing {0}', array($project->getName()));

        /* @TODO: make directory configurable */
        $cwd = getcwd().'/var/projects/'.$project->getName();
        if (!is_dir($cwd)) {
            $repo = Admin::cloneRepository($cwd, $project->getOrigin());
        } else {
            $repo = new Repository($cwd);
        }

        $branches = $project->getBranches();
        $logger   = $this->logger;
        $runner   = $this->runner;

        $repo->setLogger($logger);
        /* @var Branch $branch */
        foreach ($repo->getReferences()->getBranches() as $branch) {
            $branchName = $branch->getName();
            if (!\in_array($branchName, $branches)) {
                continue;
            }
            $logger->info('processing branch {0}', array($branchName));
            $repo->run('checkout', array('-q', $branchName));

            foreach ($project->getPrefixes() as $prefix) {
                $key          = $prefix['key'];
                $name         = substr($key, stripos($key, '/') + 1);
                $target       = "split/${name}";
                $remoteTarget = $prefix['target'];
                $runner->run($this->splitsh." --prefix=${key} --target=${target} --progress", $cwd);
                $repo->run('checkout', array('-q', $target));
                $repo->run('push', array('-u', $remoteTarget, "{$target}:refs/heads/{$branchName}"));
                $repo->run('checkout', array('-q', 'master'));
            }
        }
    }
}
