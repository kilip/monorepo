<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Config;

use Monorepo\Config\Project;
use Monorepo\Console\Logger;

class Config
{
    /**
     * @var Project[]
     */
    private $config;
    private $configFile;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var bool
     */
    private $hasError;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->hasError = false;
    }

    /**
     * @param string $name
     *
     * @return Project
     */
    public function getProject($name)
    {
        if (!isset($this->config[$name])) {
            throw new \InvalidArgumentException(sprintf('Project "%s" not exist.'));
        }

        return $this->config[$name];
    }

    /**
     * @return Project[]
     */
    public function getProjects()
    {
        return $this->config;
    }

    public function setConfigFile($file)
    {
        $this->parse($file);
        $this->configFile = $file;
    }

    /**
     * @param $file
     *
     * @throws \InvalidArgumentException if file not exists or unreadable
     */
    private function parse($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new \InvalidArgumentException(sprintf(
                'The config file "%s" is not exist or unreadable.',
                $file
            ));
        }

        $contents = file_get_contents($file);
        $json = json_decode($contents, true);
        if (json_last_error()) {
            throw new \InvalidArgumentException(
                sprintf('Error reading config from "%s". Json error: "%s"', $file, json_last_error_msg())
            );
        }

        $config = array();
        foreach ($json as $name => $value) {
            $config[$name] = new Project($this->logger, $name, $value);
        }

        if ($this->logger->hasErrored()) {
            throw new \InvalidArgumentException(
                sprintf('Can not parse config file "%s". Please check your configuration file again.', $file)
            );
        }
        $this->config = $config;
    }
}
