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

use Monorepo\Console\Logger;

class Config
{
    /**
     * Project lists.
     *
     * @var Project[]
     */
    private $config;

    /**
     * Json config file to use.
     *
     * @var string
     */
    private $configFile;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Config constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $name
     *
     * @return Project
     */
    public function getProject($name)
    {
        if (!isset($this->config[$name])) {
            throw new \InvalidArgumentException(sprintf('Project "%s" not exist.', $name));
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

    /**
     * @param string Json format contents
     *
     * @throws \InvalidArgumentException if file not exists or unreadable
     */
    public function parse($contents)
    {
        $json = json_decode($contents, true);
        if (json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        $config = array();
        foreach ($json as $name => $value) {
            $config[$name] = new Project($this->logger, $name, $value);
        }

        $this->config = $config;
    }

    /**
     * @param string $file
     */
    public function parseFile($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new \InvalidArgumentException(sprintf(
                'The config file "%s" is not exist or unreadable.',
                $file
            ));
        }

        $contents = file_get_contents($file);

        try {
            $this->parse($contents);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Error reading config from "%s". Error message: "%s"', $file, $e->getMessage())
            );
        }

        $this->configFile = $file;
    }
}
