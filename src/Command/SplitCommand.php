<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dotfiles\Monorepo\Command;

use Dotfiles\Monorepo\Exception\CommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Class Split.
 *
 * @author      Anthonius Munthi <me@itstoni.com>
 */
class SplitCommand extends AbstractCommand
{
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

    /**
     * @throws CommandException when splitsh-lite bin is not found
     */
    protected function configure()
    {
        $help = <<<'EOC'

This commmand will split a repository
into a defined target in <info>.monorepo.yml</info> file.

EOC;

        $this
            ->setName('split')
            ->setDescription('Split a repository')
            ->setHelp($help)
        ;
        $dirs = array(
            // in development vendor dir
            __DIR__.'/../../vendor/bin',

            // in vendor mode bin dir
            __DIR__.'/../../../bin',

            // in library bin dir,
            __DIR__.'/../../../../bin',
        );
        $execFinder = new ExecutableFinder();
        $file = $execFinder->find('splitsh-lite', null, $dirs);

        if (!is_file($file)) {
            throw new CommandException(
                sprintf('Can\'t find "splitsh-lite" executable file.')
            );
        }
        $this->splitshLitePath = $file;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln((string) $input->getOption('dry-run'));
    }
}
