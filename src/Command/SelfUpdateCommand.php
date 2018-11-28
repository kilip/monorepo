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
use Monorepo\Console\Application;
use Monorepo\Console\Logger;
use Monorepo\Processor\Downloader;
use Monorepo\Processor\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends AbstractCommand
{
    const BASE_URL = 'https://sourceforge.net/projects/monorepo/files/%%file%%/download';

    /**
     * @var string
     */
    private $branchAlias;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $date;

    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $pharFile;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $versionFile;

    public function __construct(
        Downloader $downloader,
        Config $config,
        Filesystem $filesystem,
        Logger $logger
    ) {
        $this->config     = $config;
        $this->downloader = $downloader;
        $this->tempDir    = sys_get_temp_dir().'/monorepo';
        $this->cacheDir   = $config->getCacheDir();
        $this->fs         = $filesystem;
        $this->logger     = $logger;

        parent::__construct('self-update');
    }

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(['selfupdate'])
            ->setDescription('Update monorepo to latest version')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception when version file is invalid
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tempDir     = $this->tempDir;
        $baseUrl     = static::BASE_URL;
        $url         = str_replace('%%file%%', 'monorepo.phar.json', $baseUrl);
        $versionFile = $tempDir.'/update/monorepo.phar.json';
        $fs          = $this->fs;
        $logger      = $this->logger;

        $output->writeln('Start checking new version');

        $fs->mkdir(\dirname($versionFile));

        $downloader = $this->downloader;
        $downloader->run($url, $versionFile);
        $contents = file_get_contents($versionFile);
        if ('' === trim($contents)) {
            throw new \Exception('Can not parse monorepo.phar.json file');
        }
        $json = json_decode($contents, true);

        $this->versionFile = $versionFile;
        $this->version     = $json['version'];
        $this->branchAlias = $json['branch'];
        $this->date        = $json['date'];

        if (Application::VERSION !== $this->version) {
            $this->doUpdate($output);
            $this->getApplication()->get('clear-cache')->run($input, $output);
        } else {
            $logger->info('You already have latest monorepo version');
        }
    }

    private function doUpdate(OutputInterface $output)
    {
        $fs      = $this->fs;
        $tempDir = $this->tempDir.'/update/'.$this->version;
        $fs->copy($this->versionFile, $tempDir.\DIRECTORY_SEPARATOR.'VERSION');

        $targetFile = $tempDir.\DIRECTORY_SEPARATOR.'monorepo.phar';
        if (!is_file($targetFile)) {
            $baseUrl     = static::BASE_URL;
            $url         = str_replace('%%file%%', 'monorepo.phar', $baseUrl);
            $downloader  = $this->downloader;
            $downloader->run($url, $targetFile);
        }

        $this->pharFile = $targetFile;
        $cacheDir       = $this->cacheDir;

        // copy current phar into new dir
        // we can't coverage or test phar environment
        //@codeCoverageIgnoreStart
        $current = \Phar::running(false);
        $output->writeln($current);
        if (is_file($current)) {
            $override = ['override' => true];
            $backup   = $cacheDir.'/monorepo_old.phar';
            $fs->copy($current, $backup, $override);
            $fs->copy($this->pharFile, $current, $override);
            $output->writeln('Your <comment>monorepo.phar</comment> is updated.');
        }
        //@codeCoverageIgnoreEnd
    }
}
