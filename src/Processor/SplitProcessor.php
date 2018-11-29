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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        return [
            SplitCommand::SPLIT_EVENT => 'onSplit',
        ];
    }

    public function onSplit()
    {
        $config  = $this->config;

        foreach ($config->getProjects() as $project) {
            $this->processProject($project);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function configure()
    {
        $file          = __DIR__.'/../../vendor/toni/splitsh/bin/splitsh-lite';
        $this->splitsh = $file;
    }

    private function processProject(Project $project)
    {
        $logger      = $this->logger;
        $config      = $this->config;
        $monorepoDir = $config->getMonorepoDir();
        $cwd         = $monorepoDir.\DIRECTORY_SEPARATOR.$project->getName();

        $this->logger->info('processing {0}', [$project->getName()]);

        if (!is_dir($cwd)) {
            $logger->info('cloning from {0} into {1}', [$project->getOrigin(), $cwd]);
            $repo = Admin::cloneRepository($cwd, $project->getOrigin());
        } else {
            $repo = new Repository($cwd);
        }

        $branches = $project->getBranches();
        $logger   = $this->logger;
        $runner   = $this->runner;

        //$repo->setLogger($logger);
        /* @var Branch $branch */
        foreach ($branches as $branchName) {
            $repo->run('checkout', ['-q', $branchName]);
            $repo->run('pull', ['origin', $branchName]);
            $logger->info('processing branch {0}', [$branchName]);
            foreach ($project->getPrefixes() as $prefix) {
                $key          = $prefix['key'];
                $name         = substr($key, stripos($key, '/') + 1);
                $target       = "split/${name}";
                $remoteTarget = $prefix['target'];
                $runner->run($this->splitsh." --prefix=${key} --target=${target} --progress", $cwd);
                $repo->run('checkout', ['-q', $target]);
                $repo->run('push', ['-u', $remoteTarget, "{$target}:refs/heads/{$branchName}"]);
                $repo->run('checkout', ['-q', 'master']);
            }
        }
    }
}
