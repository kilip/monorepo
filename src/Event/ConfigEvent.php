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

namespace Monorepo\Event;

use Monorepo\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

class ConfigEvent extends Event
{
    /**
     * @var string
     */
    private $configFile;

    /**
     * @return string|null
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * @param string $configFile
     *
     * @throws InvalidArgumentException When config file not exists or not readable
     */
    public function setConfigFile(string $configFile)
    {
        if (!is_file($configFile) || !is_readable($configFile)) {
            throw new InvalidArgumentException(
                sprintf('The config file "%s" not exists or not readable.', $configFile)
            );
        }

        $this->configFile = $configFile;
    }
}
