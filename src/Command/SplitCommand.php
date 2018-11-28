<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Command;

use Monorepo\Event\EventDispatcher;
use Monorepo\Exception\CommandException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Split.
 *
 * @author      Anthonius Munthi <https://itstoni.com>
 */
class SplitCommand extends AbstractCommand implements CommandInterface
{
    public const SPLIT_EVENT      = 'split.events.split';
    public const SPLIT_EVENT_POST = 'split.events.post';
    public const SPLIT_EVENT_PRE  = 'split.events.pre';

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * splitsh-lite bin path.
     *
     * @var string
     */
    private $splitshLitePath;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcher $dispatcher
    ) {
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;

        parent::__construct('split');
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
            ->addArgument('branch', InputArgument::OPTIONAL, 'Branch name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dispatcher = $this->dispatcher;
        $logger     = $this->logger;

        $logger->debug('dispatching event {0}', array(self::SPLIT_EVENT_PRE));
        $dispatcher->dispatch(self::SPLIT_EVENT_PRE);

        $logger->debug('dispatching event {0}', array(self::SPLIT_EVENT));
        $dispatcher->dispatch(self::SPLIT_EVENT);

        $logger->debug('dispatching event {0}', array(self::SPLIT_EVENT_POST));
        $dispatcher->dispatch(self::SPLIT_EVENT_POST);
    }
}
