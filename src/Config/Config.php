<?php

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
     * monorepo.json file to use.
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
     * @param string $contents Json format contents
     *
     * @throws \InvalidArgumentException if file not exists or unreadable
     */
    public function parse($contents)
    {
        $config = array();
        $json = json_decode($contents, true);

        if (!$this->validate($contents)) {
            throw new \InvalidArgumentException('Monorepo config is not valid. Please check previous error!');
        }

        foreach ($json as $item) {
            $name = $item['name'];
            $config[$name] = new Project($this->logger, $name, $item);
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

    /**
     * Validate contents.
     *
     * @param string $contents
     *
     * @return bool
     */
    private function validate($contents)
    {
        $schemaFile = realpath(__DIR__.'/../../config/schema.json');
        $schema = (object) array('$ref' => 'file://'.$schemaFile);
        $validator = new Validator();
        $logger = $this->logger;
        $json = json_decode($contents);

        $validator->validate($json, $schema);
        foreach ($validator->getErrors() as $error) {
            $message = sprintf('[%s] %s', $error['property'], $error['message']);
            $logger->error($message);
        }

        return $validator->isValid();
    }
}
