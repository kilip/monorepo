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

class Project
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        Logger $logger,
        $name,
        $config
    ) {
        $default = array(
            'ignored-tags' => array(),
            'tags' => array(),
            'branches' => array('master'),
        );

        $this->logger = $logger;
        $config = array_merge($default, $config);
        $config = $this->validate($config);
        $this->config = $config;
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getBranches()
    {
        return $this->config['branches'];
    }

    /**
     * @return array
     */
    public function getIgnoredTags()
    {
        return $this->config['ignored-tags'];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->config['origin'];
    }

    /**
     * @return array
     */
    public function getPrefixes()
    {
        return $this->config['prefixes'];
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->config['target'];
    }

    private function validate($config)
    {
        if (
            isset($config['ignored-tags'])
            && is_string($tags = $config['ignored-tags'])
        ) {
            $exp = explode(' ', $tags);
            $config['ignored-tags'] = $exp;
        }

        return $config;
    }
}
