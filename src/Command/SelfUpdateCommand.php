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
use Monorepo\Exception\HttpNotFoundException;
use Monorepo\Processor\Downloader;
use Monorepo\Processor\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends AbstractCommand
{
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
     * Use stable source.
     *
     * @var bool
     */
    private $stable = true;

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
        $this->cacheDir   = $config->getMonorepoDir().'/releases';
        $this->fs         = $filesystem;
        $this->logger     = $logger;
        parent::__construct('self-update');
    }

    public function downloadVersionFile($target, $stable = true)
    {
        try {
            $url        = $this->config->getVersionUrl($stable);
            $downloader = $this->downloader;
            $downloader->run($url, $target);
            $contents = file_get_contents($target);

            return json_decode($contents, true);
        } catch (HttpNotFoundException $exception) {
            return false;
        }
    }

    public function update()
    {
        $fs          = $this->fs;
        $tempDir     = $this->tempDir.'/update/'.$this->version;
        $downloader  = $this->downloader;
        $url         = $this->config->getPharUrl($this->stable);
        $targetFile  = $tempDir.\DIRECTORY_SEPARATOR.'mr.phar';

        $fs->copy($this->versionFile, $tempDir.\DIRECTORY_SEPARATOR.'VERSION');

        if (!is_file($targetFile)) {
            $downloader->run($url, $targetFile);
        }

        if (is_file($targetFile)) {
            $this->createPhar($this->cacheDir, $targetFile);
        }
    }

    public function validateVersion()
    {
        $fs              = $this->fs;
        $tempDir         = $this->tempDir;
        $versionFile     = $tempDir.'/update/mr.phar.json';
        $logger          = $this->logger;

        $logger->info('start checking new version');
        $fs->mkdir(\dirname($versionFile));

        if (false == ($json = $this->downloadVersionFile($versionFile))) {
            $this->stable = false;
            $logger->info('no stable version found');
            $logger->info('checking nightly build version');
            $json = $this->downloadVersionFile($versionFile, false);
        }

        if (!\is_array($json)) {
            throw new \RuntimeException('Update failed. Can not find version file to update');
        }

        $this->versionFile = $versionFile;
        $this->version     = $json['version'];
        $this->branchAlias = $json['branch'];
        $this->date        = $json['date'];

        return $this->getApplication()->getVersion() === $json['version'];
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
        $logger      = $this->logger;
        if (!$this->validateVersion()) {
            $this->update();
            $this->getApplication()->get('clear-cache')->run($input, $output);
        } else {
            $logger->info('You already have the latest version.');
        }
    }

    /**
     * @param string $cacheDir
     * @param string $targetFile
     * @codeCoverageIgnore
     */
    private function createPhar($cacheDir, $targetFile)
    {
        // we can't coverage or test phar environment
        $current = \Phar::running(false);
        $logger  = $this->logger;
        $fs      = $this->fs;

        $logger->info($current);
        if (is_file($current)) {
            $override = ['override' => true];
            $backup   = $cacheDir.'/mr_old.phar';
            $fs->copy($current, $backup, $override);
            $fs->copy($targetFile, $current, $override);
            $logger->info('Your {0} is updated.', ['monorepo']);
        }
    }
}
