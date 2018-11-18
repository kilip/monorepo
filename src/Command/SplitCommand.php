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

use Monorepo\Exception\CommandException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class Split.
 *
 * @author      Anthonius Munthi <me@itstoni.com>
 */
class SplitCommand extends AbstractCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * splitsh-lite bin path.
     *
     * @var string
     */
    private $splitshLitePath;

    /**
     * Find executable file for splitsh-lite.
     *
     * @return string Filename of splitsh-lite bin
     */
    final public function getSplitLiteExecutable()
    {
        return $this->splitshLitePath;
    }

    public function handleProcessRun($type, $buffer)
    {
        $contents = $buffer;
        if (Process::ERR == $type) {
            $contents = '<error>'.$buffer.'</error>';
        }
        $this->io->write($contents, OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    /**
     * @throws CommandException when splitsh-lite bin is not found
     */
    protected function configure()
    {
        $help = <<<'EOC'

This commmand will split a repository
into a defined target in <info>monorepo.json</info> file.

EOC;

        $this
            ->setName('split')
            ->setDescription('Split a repository')
            ->setHelp($help)
            ->addArgument('branch', InputArgument::OPTIONAL, 'Branch name');
        $dirs = array(
            // in development vendor dir
            __DIR__.'/../../vendor/bin',

            // in vendor mode bin dir
            __DIR__.'/../../../bin',

            // in library bin dir,
            __DIR__.'/../../../../bin',
        );
        $execFinder = new ExecutableFinder();
        $file = $execFinder->find('splitsh', null, $dirs);

        if (!is_file($file)) {
            throw new CommandException(
                sprintf('Can\'t find "splitsh" executable file.')
            );
        }
        $this->splitshLitePath = realpath($file);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Load configuration file
        $configFile = $input->getOption('config');

        if (!file_exists($configFile)) {
            $output->writeln(sprintf('<error>Configuration file "%s" does not exist.</error>', $configFile));

            return 1;
        }

        $io = $this->io = new SymfonyStyle($input, $output);

        $this->io->newLine(2);
        $io->section('Reading Configuration');
        $io->writeln(sprintf('Using configuration file "%s".', $configFile), OutputInterface::VERBOSITY_VERBOSE);
        $branchFilter = $input->getArgument('branch');
        $output->writeln('Reading configuration file...', OutputInterface::VERBOSITY_VERBOSE);

        $configuration = file_get_contents($configFile);
        if (getenv('GITHUB_TOKEN')) {
        }
        $configuration = json_decode(file_get_contents($configFile), true);
        if (!count($configuration)) {
            $output->writeln('Configuration is empty.');
        }

        $projects = array();
        foreach ($configuration as $project => $config) {
            $commands = array();
            $commands[] = 'pwd';
            $this->io->writeln("Processing <info>${project}</info> config");
            foreach ($config['branches'] as $branch) {
                if (!is_null($branchFilter) && $branch !== $branchFilter) {
                    continue;
                }
                $commands[] = "git checkout -q ${branch}";
                foreach ($config['prefixes'] as $prefix) {
                    $key = $prefix['key'];
                    $name = substr($key, stripos($key, '/') + 1);
                    $target = "split/${name}";
                    $remoteTarget = $prefix['target'];
                    $commands[] = "splitsh --prefix=${key} --target=${target} --progress";
                    $commands[] = "git checkout -q ${target}";
                    $commands[] = "git push -u ${remoteTarget} ${target}:refs/heads/${branch}";
                    $commands[] = 'git checkout -q master';
                    $io->writeln("[${branch}] added prefix ${key}~> ${target} ~> ${remoteTarget}");
                }
            }

            $projects[$project] = array(
                'path' => $config['path'],
                'commands' => $commands,
            );
        }

        $this->io->newLine(2);
        $io->section('Start processing split');
        // executing command per project
        foreach ($projects as $name => $config) {
            array_walk($config['commands'], array($this, 'exec'), $config['path']);
        }
    }

    private function exec($command, $key, $path)
    {
        $this->io->writeln($command, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $command = str_replace('splitsh', $this->getSplitLiteExecutable(), $command);
        $process = new Process(
            $command,
            $path
        );
        if (!$this->dryRun) {
            $process->run(array($this, 'handleProcessRun'));
        }
    }
}
