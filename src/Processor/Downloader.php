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

use Monorepo\Console\Logger;
use Monorepo\Exception\HttpNotFoundException;
use Monorepo\Exception\NetworkConnectionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    /**
     * @var int
     */
    private $bytesMax;

    /**
     * @var bool
     */
    private $connected;

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

    private $hasStarted = false;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var Logger
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

    public function __construct(InputInterface $input, OutputInterface $output, Logger $logger, Filesystem $filesystem)
    {
        $this->output      = $output;
        $this->logger      = $logger;
        $this->progressBar = new ProgressBar(new ConsoleOutput());
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

    public function handleError($code, $message)
    {
        $logger         = $this->logger;
        $this->hasError = true;
        $logger->error('code: {0} message: {1}', [$code, $message]);
    }

    public function handleNotification($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax)
    {
        $logger = $this->logger;

        switch ($notificationCode) {
            case STREAM_NOTIFY_CONNECT:
                $logger->debug('connected to server');
                $this->connected = true;

                break;
            case STREAM_NOTIFY_REDIRECTED:
                $this->output->writeln('');
                $this->logger->info('Download redirected to {0}', [$message]);
                $this->progressBar->clear();

                break;
            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->bytesMax = $bytesMax;

                break;
            case STREAM_NOTIFY_MIME_TYPE_IS:
                $logger->debug('mime type is: {0}', [$message]);

                break;
            case STREAM_NOTIFY_PROGRESS:
                $this->updateProgressBar($bytesTransferred);

                break;
            case STREAM_NOTIFY_COMPLETED:
                $this->updateProgressBar($bytesMax);

                break;
            case STREAM_NOTIFY_FAILURE:
                throw new HttpNotFoundException($message);

                break;
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_AUTH_RESULT:
            default:
                $logger->info('Download failed code: {0} message: {1}', [$notificationCode, $message]);

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
        $input            = $this->input;
        $dryRun           = $input->hasParameterOption('dry-run');
        $fullName         = basename($targetFile);
        $fs               = $this->fs;
        $output           = $this->output;
        $logger           = $this->logger;
        $this->hasStarted = false;
        $this->connected  = false;
        $this->bytesMax   = null;

        $logger->debug('downloading {0} to {1}', [$url, $targetFile]);
        $fs->mkdir(\dirname($targetFile));
        $this->progressBar->setFormat("Download <comment>$fullName</comment>: <comment>%percent:3s%%</comment> <info>%estimated:-6s%</info>");

        $this->hasError = false;
        $logger->info('Downloading {0}', [$url]);
        if (!$dryRun) {
            $context = stream_context_create([], ['notification' => [$this, 'handleNotification']]);
            set_error_handler([$this, 'handleError']);

            try {
                $this->contents = file_get_contents($url, false, $context);
            } catch (\Exception $exception) {
                if ($exception instanceof HttpNotFoundException && $this->connected) {
                    throw $exception;
                } else {
                    throw new NetworkConnectionException($exception->getMessage(), 1, $exception);
                }
            }
            restore_error_handler();
            if ($this->hasError) {
                $output->writeln("\n");

                throw new NetworkConnectionException('Failed to download '.$url);
            }

            file_put_contents($targetFile, $this->contents, LOCK_EX);
        }
        $output->writeln('');
        $this->logger->debug('Download <comment>finished</comment>');
        $output->writeln('');
    }

    public function updateProgressBar($bytesTransferred)
    {
        $progressBar = $this->progressBar;
        $bytesMax    = $this->bytesMax;

        if (!$this->hasStarted) {
            $progressBar->start($bytesMax);
        }

        $progressBar->setProgress($bytesTransferred);
    }
}
