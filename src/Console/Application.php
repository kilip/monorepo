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

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    public const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    public const RELEASE_DATE = '@release_date@';

    public const VERSION = '@package_version@';

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

        $className = 'Monorepo\Command\CompileCommand';
        if (class_exists($className)) {
            $this->add(new $className());
        }

        $this->configured = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLongVersion()
    {
        return implode(' ', [
            static::VERSION,
            static::BRANCH_ALIAS_VERSION,
            static::RELEASE_DATE,
        ]);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configured) {
            parent::configureIO($input, $output);
        }
    }
}
