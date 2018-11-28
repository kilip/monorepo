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

class Project implements \Serializable
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        $name,
        $config
    ) {
        $default = [
            'ignored-tags' => [],
            'tags'         => [],
            'branches'     => ['master'],
        ];

        $config       = array_merge($default, $config);
        $this->config = $config;
        $this->name   = $name;
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

    public function serialize()
    {
        return \serialize([
            'name'   => $this->name,
            'config' => $this->config,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $serialized   = unserialize($serialized);
        $this->name   = $serialized['name'];
        $this->config = $serialized['config'];
    }
}
