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

namespace Monorepo\Console;

use Monorepo\Processor\Filesystem;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends BaseApplication
{
    const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    const RELEASE_DATE = '@release_date@';

    const VERSION = '@package_version@';

    private $configured;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct('monorepo', self::VERSION);
        $this->configureIO($input, $output);
        $this->setup();
    }

    private function setup()
    {
        $this->getDefinition()->addOptions([
            new InputOption(
                'config',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Configuration file to be used'
            ),
            new InputOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Do not do real change, just show debug output only'
            ),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLongVersion()
    {
        return implode(' ', [
            '<info>'.static::VERSION.'</info>',
            '<comment>'.static::BRANCH_ALIAS_VERSION.'</comment>',
            '<info>'.static::RELEASE_DATE.'</info>',
        ]);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $className = 'Monorepo\Command\CompileCommand';
        if (class_exists($className)) {
            $fs     = $container->get(Filesystem::class);
            $logger = $container->get(Logger::class);
            $this->add(new $className($logger, $fs));
        }

        $this->configured = true;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configured) {
            parent::configureIO($input, $output);
        }
    }
}
