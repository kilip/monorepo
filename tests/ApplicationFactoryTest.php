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

namespace MonorepoTest;

use Monorepo\ApplicationFactory;
use Monorepo\Config\Config;
use Monorepo\Console\Application;
use Monorepo\Console\Logger;
use Monorepo\Event\ConfigEvent;
use Monorepo\Event\EventDispatcher;
use Monorepo\Test\JsonConfigFileTrait;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class TestApplicationFactory extends ApplicationFactory
{
    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}

/**
 * Class ApplicationFactoryTest.
 *
 * @author  Anthonius Munthi <https://itstoni.com>
 * @covers  \Monorepo\ApplicationFactory
 * @covers  \Monorepo\DI\Compiler\DefaultPass
 */
class ApplicationFactoryTest extends TestCase
{
    use OutputTrait, JsonConfigFileTrait;

    /**
     * @var MockObject
     */
    private $config;

    /**
     * @var MockObject
     */
    private $container;

    private static $cwd;

    /**
     * @var MockObject
     */
    private $dispatcher;

    /**
     * @var ApplicationFactory
     */
    private $target;

    public static function setUpBeforeClass()
    {
        self::$cwd = getcwd();
    }

    public static function tearDownAfterClass()
    {
        chdir(self::$cwd);
    }

    public function setUp()
    {
        $factory    = new TestApplicationFactory();
        $logger     = new Logger($this->getOutput());

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        $config = $this->getMockBuilder(Config::class)
            ->setConstructorArgs([$dispatcher, $logger])
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        $container  = $this->createMock(Container::class);

        $container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['monorepo.logger', 1, $logger],
                ['monorepo.dispatcher', 1, $dispatcher],
                ['monorepo.config', 1, $config],
            ])
        ;

        $this->getOutput()->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $factory->setContainer($container);

        $this->target     = $factory;
        $this->container  = $container;
        $this->dispatcher = $dispatcher;
        $this->config     = $config;

        chdir(self::$cwd);
    }

    public function onConfigNotFound(ConfigEvent $event)
    {
        $event->setConfigFile($this->generateJsonConfig1());
    }

    public function testBoot()
    {
        $app = new ApplicationFactory();
        $app->boot();

        $container = $app->getContainer();

        $cacheDir = realpath(__DIR__.'/../var/cache');
        $rootDir  = realpath(__DIR__.'/../');
        $config   = $container->get(Config::class);

        $this->assertInstanceOf(Application::class, $container->get('monorepo.app'));
        $this->assertEquals($cacheDir, $config->getCacheDir());
        $this->assertEquals($rootDir, $config->getRootDir());
    }

    /**
     * @covers \Monorepo\Event\ConfigEvent
     */
    public function testCompileConfig()
    {
        $dispatcher = $this->dispatcher;
        $container  = $this->container;
        $target     = $this->getMockBuilder(TestApplicationFactory::class)
            ->setMethods(['compileConfigFile'])
            ->getMock()
        ;

        $dispatcher->addListener(Config::EVENT_CONFIG_NOT_FOUND, [$this, 'onConfigNotFound']);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Config::EVENT_CONFIG_NOT_FOUND, $this->isInstanceOf(ConfigEvent::class))
        ;

        $target->loadEnv();
        $target->expects($this->once())
            ->method('compileConfigFile')
            ->with(getcwd().'/var/cache', $this->generateJsonConfig1())
            ->willReturn(null)
        ;

        $target->setContainer($container);
        $target->compileConfig();
    }

    /**
     * @throws \Exception
     * @covers \Monorepo\Config\Project::serialize
     * @covers \Monorepo\Config\Project::unserialize
     */
    public function testCompileConfigFile()
    {
        $target     = $this->target;
        $cacheDir   = $this->getTempDir().'/cache';
        $configFile = $this->generateJsonConfig1();
        $id         = crc32($configFile);
        $cacheFile  = $cacheDir.\DIRECTORY_SEPARATOR.$id.'.dat';
        $config     = $this->config;

        @mkdir($cacheDir, 0777, true);
        @unlink($cacheFile);

        // setConfig() should be called twice
        $config->expects($this->once())
            ->method('setProjects')
            ->with($this->isType('array'))
        ;

        $config->expects($this->once())
            ->method('parseFile')
            ->with($configFile)
        ;

        $target->compileConfigFile($cacheDir, $configFile);

        $display = $this->getDisplay();
        $this->assertContains('compiling config file', $display);
        $this->assertContains('writing config file to', $display);
        $this->assertFileExists($cacheFile);

        // cache should be exists now, verify using cache now
        $target->compileConfigFile($cacheDir, $configFile);

        $display = $this->getDisplay();
        $this->assertContains(sprintf('loading config cache from "%s.dat"', $id), $display);
    }

    public function testCompileConfigWithConfigFileExists()
    {
        $container  = $this->container;
        $file       = $this->generateJsonConfig1();
        $cwd        = sys_get_temp_dir().'/monorepo';
        $configFile = $cwd.'/monorepo.json';
        copy($file, $configFile);

        chdir($cwd);
        $target = $this->getMockBuilder(TestApplicationFactory::class)
            ->setMethods(['compileConfigFile'])
            ->getMock()
        ;

        $target->loadEnv();

        $target->expects($this->once())
            ->method('compileConfigFile')
            ->with(getcwd().'/var/cache', $configFile)
            ->willReturn(null)
        ;

        $target->setContainer($container);
        $target->compileConfig();
    }
}
