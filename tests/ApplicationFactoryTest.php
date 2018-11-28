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
    public function setContainer(Container $container): void
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

    /**
     * @var MockObject
     */
    private $dispatcher;

    /**
     * @var ApplicationFactory
     */
    private $target;

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

        $this->assertInstanceOf(Application::class, $container->get('monorepo.app'));
    }

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
            ->method('setConfig')
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
}
