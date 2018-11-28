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
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication
{
    public const BRANCH_ALIAS_VERSION = '@package_branch_alias_version@';

    public const RELEASE_DATE = '@release_date@';

    public const VERSION = '@package_version@';

    public function __construct()
    {
        parent::__construct('monorepo', self::VERSION);
        $this->setup();
    }

    private function setup()
    {
        $this->getDefinition()->addOptions(array(
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
        ));
    }
}
