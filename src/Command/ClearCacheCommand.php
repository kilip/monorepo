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

namespace Monorepo\Command;

use Monorepo\Config\Config;
use Monorepo\Console\Logger;
use Monorepo\Processor\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ClearCacheCommand extends AbstractCommand
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ClearCacheCommand constructor.
     *
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(Config $config, Logger $logger, Filesystem $fs)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->fs     = $fs;
        parent::__construct('clear-cache');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config   = $this->config;
        $logger   = $this->logger;
        $cacheDir = $config->getCacheDir();
        $fs       = $this->fs;

        $logger->debug('cleaning cache in directory {0}', [$cacheDir]);

        $finder = Finder::create()
            ->in($cacheDir)
        ;

        foreach ($finder->files() as $file) {
            $fs->remove($file);
        }
    }

    protected function configure()
    {
        $home = getenv('HOME');
        $help = <<<EOC
Clear cache command will clear all configuration cache in this directory:
    <info>${home}/.monorepo/cache</info>

EOC;

        $this
            ->setAliases(['cc'])
            ->setDescription('Clear configuration cache')
            ->setHelp($help)
        ;
    }
}
