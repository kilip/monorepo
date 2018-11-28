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

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    /**
     * @var int
     */
    private $bytesMax;

    /**
     * @var string
     */
    private $contents;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var bool
     */
    private $hasError = false;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    public function __construct(InputInterface $input, OutputInterface $output, LoggerInterface $logger, Filesystem $filesystem)
    {
        $this->output      = $output;
        $this->logger      = $logger;
        $this->progressBar = new ProgressBar($output);
        $this->input       = $input;
        $this->fs          = $filesystem;
    }

    /**
     * @return ProgressBar
     */
    public function getProgressBar(): ProgressBar
    {
        return $this->progressBar;
    }

    public function handleError($bar, $message)
    {
        $this->hasError = true;
        $this->output->writeln("<comment>Error:</comment>\n<info>${message}</info>\n");
    }

    public function handleNotification($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax)
    {
        switch ($notificationCode) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                // handle error here
                break;
            case STREAM_NOTIFY_REDIRECTED:
                $this->logger->info('');
                $this->logger->info('Download redirected!');
                $this->progressBar->clear();

                break;
            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->progressBar->start($bytesMax);
                $this->bytesMax = $bytesMax;

                break;
            case STREAM_NOTIFY_PROGRESS:
                $this->progressBar->setProgress($bytesTransferred);

                break;
            case STREAM_NOTIFY_COMPLETED:
                $this->progressBar->setProgress($bytesMax);

                break;
        }
    }

    /**
     * @param string $url
     * @param string $targetFile
     * @codeCoverageIgnore
     */
    public function run(string $url, string $targetFile)
    {
        $input    = $this->input;
        $dryRun   = $input->hasParameterOption('dry-run');
        $fullName = basename($targetFile);
        $fs       = $this->fs;

        $fs->mkdir(\dirname($targetFile));

        $this->progressBar->setFormat("Download <comment>$fullName</comment>: <comment>%percent:3s%%</comment> <info>%estimated:-6s%</info>");

        $this->hasError = false;
        $this->logger->debug('Downloading {0} to {1}', [$url, $targetFile]);
        if (!$dryRun) {
            $context = stream_context_create([], ['notification' => [$this, 'handleNotification']]);
            set_error_handler([$this, 'handleError']);
            $this->contents = file_get_contents($url, false, $context);
            restore_error_handler();
            if ($this->hasError) {
                throw new \RuntimeException('Failed to download '.$url);
            }

            file_put_contents($targetFile, $this->contents, LOCK_EX);
        }
        $this->logger->debug('Download <comment>finished</comment>');
    }
}
