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

use Monorepo\Config\Config;
use Monorepo\Console\Logger;
use Monorepo\DI\Compiler\DefaultPass;
use Monorepo\Event\ConfigEvent;
use Monorepo\Event\EventDispatcher;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Dotenv\Dotenv;

class ApplicationFactory
{
    /**
     * @var Container
     */
    protected $container;

    public function boot(): self
    {
        $this->loadEnv();
        $this->compileContainer();

        return $this;
    }

    public function compileConfig()
    {
        $container  = $this->container;
        $dispatcher = $container->get('monorepo.dispatcher');
        $logger     = $container->get('monorepo.logger');
        $event      = new ConfigEvent();
        $cacheDir   = getenv('HOME').'/.monorepo/cache';

        $files = [
            getcwd().'/.monorepo.json',
            getcwd().'/monorepo.json',
        ];

        $configFile = null;
        foreach ($files as $file) {
            if (is_file($file)) {
                $configFile = $file;

                break;
            }
        }

        if (self::isDev()) {
            $cacheDir = getcwd().'/var/cache';
        }

        if (null === $configFile) {
            $logger->debug('No configuration file found in: {0}', [getcwd()]);
            $logger->debug('Dispatching event {0}', [Config::EVENT_CONFIG_NOT_FOUND]);
            $dispatcher->dispatch(Config::EVENT_CONFIG_NOT_FOUND, $event);
            $configFile = $event->getConfigFile();
        }

        if (null !== $configFile) {
            $this->compileConfigFile($cacheDir, $configFile);
        }
    }

    /**
     * @param string $cacheDir
     * @param string $configFile
     *
     * @return mixed|Config
     *
     * @throws \Exception
     */
    public function compileConfigFile($cacheDir, $configFile)
    {
        /* @var Logger $logger */
        /* @var EventDispatcher $dispatcher */
        /* @var Config $config */

        $container     = $this->container;
        $logger        = $container->get('monorepo.logger');
        $config        = $container->get('monorepo.config');
        $id            = crc32($configFile);
        $configFile    = realpath($configFile);
        $cacheFileName = $cacheDir.\DIRECTORY_SEPARATOR.$id.'.dat';
        $cache         = new ConfigCache($cacheFileName, !self::isDev());

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        if (!$cache->isFresh()) {
            $logger->debug('compiling config file {0}', [$configFile]);
            $config->parseFile($configFile);
            $resources = [
                new FileResource($configFile),
            ];
            $content = serialize($config->getProjects());
            $cache->write($content, $resources);
            $logger->debug('writing config file to {0}', [$cacheFileName]);
        } else {
            $logger->debug('loading config cache from "{0}"', [$id.'.dat']);
            $cacheContents = file_get_contents($cacheFileName);
            $unserialized  = unserialize($cacheContents);
            $config->setProjects($unserialized);
        }
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    public static function getEnv()
    {
        return getenv('MONOREPO_ENV');
    }

    public static function isDev()
    {
        return 'dev' === self::getEnv() || 'test' === self::getEnv();
    }

    public function loadEnv()
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $dotenv = new Dotenv();
        $files  = [
            getcwd().'/.env.dist',
        ];

        foreach ($files as $file) {
            if (is_readable($file)) {
                $dotenv->load($file);
            }
        }

        $loaded = true;
    }

    private function compileContainer()
    {
        $cacheDir    = self::isDev() ? getcwd().'/var/cache' : getenv('HOME').'/.monorepo/cache';
        $cachePath   = $cacheDir.'/container.php';
        $cache       = new ConfigCache($cachePath, !self::isDev());
        $className   = 'CachedContainer';
        $builder     = new ContainerBuilder();

        // @codeCoverageIgnoreStart
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $this->processConfig($builder);

        if (!$cache->isFresh() || self::isDev()) {
            $builder->addCompilerPass(new DefaultPass());
            $builder->setParameter('monorepo.cache_dir', $cacheDir);
            $builder->compile(true);
            $dumper = new PhpDumper($builder);
            $cache->write(
                $dumper->dump(['class' => $className]),
                $builder->getResources()
            );
        }

        if (!class_exists($className)) {
            include $cachePath;
        }

        // @codeCoverageIgnoreEnd

        /* @var \Symfony\Component\DependencyInjection\Container $container */
        $container       = new $className();
        $this->container = $container;
    }

    private function processConfig(ContainerBuilder $builder)
    {
        $config  = __DIR__.'/../config';
        $locator = new FileLocator($config);
        $loader  = new YamlFileLoader($builder, $locator);

        $loader->load('services.yaml');
    }
}
