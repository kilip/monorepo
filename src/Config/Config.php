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

namespace Monorepo\Config;

use JsonSchema\Validator;
use Monorepo\ApplicationFactory;
use Monorepo\Console\Logger;
use Monorepo\Event\EventDispatcher;
use Monorepo\Exception\InvalidArgumentException;

class Config
{
    const EVENT_CONFIG_NOT_FOUND = 'config.config_not_found';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Project lists.
     *
     * @var Project[]
     */
    private $projects = [];

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(EventDispatcher $dispatcher, Logger $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getMonorepoDir()
    {
        return ApplicationFactory::isDev() ? getcwd().'/var/test-monorepo' : getcwd().'/.monorepo';
    }

    public function getProject($name)
    {
        if (!isset($this->projects[$name])) {
            throw new InvalidArgumentException(
                sprintf('Project "%s" not exist.', $name)
            );
        }

        return $this->projects[$name];
    }

    /**
     * @return Project[]
     */
    public function getProjects(): array
    {
        return $this->projects;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @param string $contents Json format contents
     *
     * @throws InvalidArgumentException if file not exists or unreadable
     */
    public function parse($contents)
    {
        $config = [];
        $json   = json_decode($contents, true);

        if (!$this->validate($contents)) {
            throw new InvalidArgumentException('Monorepo config is not valid. Please check previous error!');
        }

        foreach ($json as $item) {
            $name          = $item['name'];
            $config[$name] = new Project($name, $item);
        }
        $this->projects = $config;
    }

    /**
     * @param string $file
     */
    public function parseFile($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new InvalidArgumentException(sprintf(
                'The config file "%s" is not exist or unreadable.',
                $file
            ));
        }

        $contents = file_get_contents($file);

        try {
            $this->parse($contents);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Error reading config from "%s". Error message: "%s"', $file, $e->getMessage())
            );
        }
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param array $projects
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;
    }

    /**
     * @param string $rootDir
     */
    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Validate contents.
     *
     * @param string $contents
     *
     * @return bool
     */
    private function validate($contents)
    {
        $schemaFile = __DIR__.'/../../config/schema.json';

        if (!strpos(__FILE__, 'phar:///')) {
            $schemaFile = 'file://'.$schemaFile;
        }
        $schema     = (object) ['$ref' => $schemaFile];
        $validator  = new Validator();
        $logger     = $this->logger;
        $json       = json_decode($contents);

        $validator->validate($json, $schema);
        foreach ($validator->getErrors() as $error) {
            $message = sprintf('[%s] %s', $error['property'], $error['message']);
            $logger->error($message);
        }

        return $validator->isValid();
    }
}
