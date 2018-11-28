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

namespace Monorepo;

use Monorepo\DI\Compiler\DefaultPass;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ApplicationFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function boot(): self
    {
        $this->compileContainer();

        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function getContainerId()
    {
        global $argv;
        $command = $argv[0];

        return crc32($command);
    }

    private function compileContainer()
    {
        $containerId = $this->getContainerId();
        $cacheDir = getcwd().'/var/cache/'.$containerId;
        $cachePath = $cacheDir.'/container.php';
        $cache = new ConfigCache($cachePath, true);
        $className = 'CachedContainer'.$containerId;
        $builder = new ContainerBuilder();

        // @codeCoverageIgnoreStart
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $this->processConfig($builder);

        if (!$cache->isFresh() || 'test' === getenv('MONOREPO_ENV')) {
            $builder->addCompilerPass(new DefaultPass());

            $builder->compile(true);
            $dumper = new PhpDumper($builder);
            $cache->write(
                $dumper->dump(array('class' => $className)),
                $builder->getResources()
            );
        }

        if (!class_exists($className)) {
            include $cachePath;
        }

        // @codeCoverageIgnoreEnd

        $container = new $className();
        $this->container = $container;
    }

    private function processConfig(ContainerBuilder $builder)
    {
        $config = realpath(__DIR__.'/../config');
        $locator = new FileLocator($config);
        $loader = new YamlFileLoader($builder, $locator);

        $loader->load('services.yaml');
    }
}
