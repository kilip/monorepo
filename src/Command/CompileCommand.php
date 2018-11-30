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

use Monorepo\Console\Logger;
use Monorepo\Processor\Filesystem;
use Seld\PharUtils\Timestamps;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Class CompileCommand.
 *
 * @TODO: cleanup all mess, make it more readable
 * @codeCoverageIgnore
 */
class CompileCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var string
     */
    private $branchAliasVersion = '';

    /**
     * @var array
     */
    private $files = [];

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $version;

    /**
     * @var \DateTime
     */
    private $versionDate;

    public function __construct(Logger $logger, Filesystem $fs)
    {
        $this->fs     = $fs;
        $this->logger = $logger;
        parent::__construct();
    }

    public function compile($pharFile = 'mr.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $this->setupVersion();
        $this->generatePhar($pharFile);
    }

    /**
     * @return string
     */
    public function getBranchAliasVersion(): string
    {
        return $this->branchAliasVersion;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return \DateTime
     */
    public function getVersionDate(): \DateTime
    {
        return $this->versionDate;
    }

    protected function configure()
    {
        $this
            ->setName('compile')
            ->setDescription('generate new mr.phar')
            ->addArgument('target', InputArgument::OPTIONAL, 'Compile mr.phar into this directory', getcwd().'/build')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cwd = getcwd();
        chdir(\dirname(__DIR__.'/../../../'));

        $this->baseDir = getcwd();
        $fs            = $this->fs;
        $logger        = $this->logger;
        $this->output  = $output;

        // start compiling process
        $targetDir = realpath($input->getArgument('target'));
        $target    = $targetDir.'/mr.phar';
        $this->compile($target);
        //$this->generateVersionFile($targetDir);
        chmod($target, 0755);

        chdir($cwd);
        $fs->remove($target);
        $fs->remove($targetDir.'/vendor');

        $logger->info('Completed! Phar files generated in {0}', [$targetDir]);
    }

    /**
     * @param $phar
     * @param \SplFileInfo $file
     * @param bool         $strip
     */
    private function addFile($phar, \SplFileInfo $file, $strip = true)
    {
        $path    = $this->getRelativeFilePath($file);
        $content = file_get_contents($file->getRealPath());
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        if ('src/Console/Application.php' === $path) {
            $content = str_replace('@package_version@', $this->version, $content);
            $content = str_replace('@package_branch_alias_version@', $this->branchAliasVersion, $content);
            $content = str_replace('@release_date@', $this->versionDate->format('Y-m-d H:i:s'), $content);
        }
        $phar->addFromString($path, $content);
    }

    private function addMonorepoBin(\Phar $phar)
    {
        $content = file_get_contents($this->baseDir.'/bin/monorepo');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/monorepo', $content);
    }

    private function generatePhar($pharFile = 'mr.phar')
    {
        $finderSort = function ($a, $b) {
            return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
        };

        $baseDir = $this->baseDir;
        $logger  = $this->logger;

        $logger->info('Start registering files in {0}', [$baseDir]);
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->notName('CompileCommand.php')
            ->in($baseDir.'/config')
            ->in($baseDir.'/src')
            ->sort($finderSort)
        ;
        $this->registerFiles($finder);

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('LICENSE')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('test')
            ->exclude('demo')
            ->exclude('docs')
            ->exclude('doc')
            ->in($baseDir.'/vendor/bin')
            ->in($baseDir.'/vendor/composer')
            ->in($baseDir.'/vendor/gitonomy')
            ->in($baseDir.'/vendor/justinrainbow')
            ->in($baseDir.'/vendor/psr')
            ->in($baseDir.'/vendor/symfony')
            ->in($baseDir.'/vendor/zendframework')
            ->sort($finderSort)
        ;
        $this->registerFiles($finder);

        $this->registerFiles($finder);
        $this->files[] = new \SplFileInfo($baseDir.'/vendor/autoload.php');

        $phar = new \Phar($pharFile, 0, 'mr.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();

        $count = \count($this->files);
        $logger->info('Start processing {0} files', [$count]);
        $this->processFiles($phar);

        $this->addMonorepoBin($phar);

        $phar->stopBuffering();

        unset($phar);

        $this->generatePlatformFile($pharFile, 'linux');
        $this->generatePlatformFile($pharFile, 'darwin');
    }

    private function generatePlatformFile($pharFile, $platform)
    {
        $dir          = \dirname($pharFile);
        $platformFile = sprintf($dir.'/mr-%s.phar', $platform);
        $pharBin      = 'vendor/toni/splitsh/bin';
        $pharBinSrc   = sprintf(__DIR__.'/../../vendor/toni/splitsh/bin/%s.amd64/splitsh-lite', $platform);
        $logger       = $this->logger;

        @mkdir($pharBin, 0777, true);

        copy($pharFile, $platformFile);
        copy($pharBinSrc, $pharBin.'/splitsh-lite');
        chmod($pharBin.'/splitsh-lite', 0777);
        chmod($platformFile, 0777);

        $phar = new \Phar($platformFile);
        $phar->addEmptyDir($pharBin);
        $phar->addFile($pharBin.'/splitsh-lite');
        $phar->setStub($this->getStub());

        unset($phar);

        $util = new Timestamps($platformFile);
        $util->updateTimestamps($this->versionDate);
        $util->save($platformFile, \Phar::SHA1);
        $this->generateVersionFile($dir, $platform);

        $logger->info('compiled {0} to {1}', [$platform, $platformFile]);
    }

    private function generateVersionFile($targetDir, $platform)
    {
        $pharFileName = sprintf('mr-%s.phar', $platform);
        $targetFile   = sprintf($targetDir.'/%s.json', $pharFileName);
        $version      = $this->version;
        $branchAlias  = $this->branchAliasVersion;
        $date         = $this->versionDate->format('Y-m-d H:i:s');
        $sha256       = trim(shell_exec('sha256sum '.$targetDir.'/'.$pharFileName));
        $sha256       = trim(str_replace($targetDir.'/'.$pharFileName, '', $sha256));

        $contents = <<<EOC
{
    "version": "${version}",
    "branch": "${branchAlias}",
    "date": "${date}",
    "sha256": "${sha256}"
}

EOC;
        file_put_contents($targetFile, $contents, LOCK_EX);
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return string
     */
    private function getRelativeFilePath($file)
    {
        $realPath     = $file->getRealPath();
        $pathPrefix   = $this->baseDir.'/';
        $pos          = strpos($realPath, $pathPrefix);
        $relativePath = (false !== $pos) ? substr_replace($realPath, '', $pos, \strlen($pathPrefix)) : $realPath;

        return strtr($relativePath, '\\', '/');
    }

    private function getStub()
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of monorepo project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

// Avoid APC causing random fatal errors per https://github.com/composer/composer/issues/264
if (extension_loaded('apc') && ini_get('apc.enable_cli') && ini_get('apc.cache_by_default')) {
    if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
        ini_set('apc.cache_by_default', 0);
    } else {
        fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running composer commands.'.PHP_EOL);
        fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.'.PHP_EOL);
    }
}

putenv('MONOREPO_PHAR_MODE=1');

Phar::mapPhar('mr.phar');

EOF;

        // add warning once the phar is older than 60 days
        if (preg_match('{^[a-f0-9]+$}', $this->version)) {
            $warningTime = $this->versionDate->format('U') + 60 * 86400;
            $stub .= "define('COMPOSER_DEV_WARNING_TIME', $warningTime);\n";
        }

        $stub = $stub.<<<'EOF'
require 'phar://mr.phar/bin/monorepo';

__HALT_COMPILER();
EOF;

        return $stub;
    }

    private function processFiles($phar)
    {
        $files       = $this->files;
        $this->output->writeln('');
        $progressBar = new ProgressBar($this->output, \count($files));
        $progressBar->setFormat('Compiling <comment>%percent%%</comment>');
        $progressBar->start();
        foreach ($files as $key => $file) {
            $this->addFile($phar, $file);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->writeln("\n");
    }

    /**
     * @param Finder $finder
     */
    private function registerFiles(Finder $finder)
    {
        foreach ($finder as $file) {
            if (!\in_array($file, $this->files)) {
                $this->files[] = $file;
            }
        }
    }

    private function setupVersion()
    {
        $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
        if (0 != $process->run()) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone and that git binary is available.');
        }
        $this->version = trim($process->getOutput());

        $process = new Process('git log -n1 --pretty=%ci HEAD', __DIR__);
        if (0 != $process->run()) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone and that git binary is available.');
        }

        $this->versionDate = new \DateTime(trim($process->getOutput()));
        $this->versionDate->setTimezone(new \DateTimeZone('UTC'));
        $process = new Process('git describe --tags --exact-match HEAD');
        if (0 == $process->run()) {
            $this->version = trim($process->getOutput());
        } else {
            // get branch-alias defined in composer.json for dev-master (if any)
            $localConfig = getcwd().'/composer.json';
            $contents    = file_get_contents($localConfig);
            $json        = json_decode($contents, true);
            if (isset($json['extra']['branch-alias']['dev-master'])) {
                $this->branchAliasVersion = $json['extra']['branch-alias']['dev-master'];
            }
        }
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!\function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (\is_string($token)) {
                $output .= $token;
            } elseif (\in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }
}
