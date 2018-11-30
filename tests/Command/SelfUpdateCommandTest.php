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

namespace MonorepoTest\Command;

use Monorepo\Command\ClearCacheCommand;
use Monorepo\Command\SelfUpdateCommand;
use Monorepo\Config\Config;
use Monorepo\Console\Application;
use Monorepo\Console\Logger;
use Monorepo\Processor\Downloader;
use Monorepo\Processor\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestSelfUpdateCommand extends SelfUpdateCommand
{
    public function run($input, $output)
    {
        $this->execute($input, $output);
    }
}

/**
 * Class SelfUpdateCommandTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Command\SelfUpdateCommand
 */
class SelfUpdateCommandTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $config;
    /**
     * @var MockObject
     */
    private $downloader;

    /**
     * @var MockObject
     */
    private $fs;

    /**
     * @var MockObject
     */
    private $input;

    private $json;

    /**
     * @var MockObject
     */
    private $logger;

    /**
     * @var MockObject
     */
    private $output;

    public function setUp()
    {
        $this->downloader = $this->createMock(Downloader::class);
        $this->config     = $this->createMock(Config::class);
        $this->fs         = $this->createMock(Filesystem::class);
        $this->logger     = $this->createMock(Logger::class);
        $this->input      = $this->createMock(InputInterface::class);
        $this->output     = $this->createMock(OutputInterface::class);
        $this->json       = <<<'JSON'
{
    "version": "some-version",
    "branch": "some-branch",
    "date": "some-date"
}
JSON;
    }

    public function testDownloadVersionFile()
    {
        $downloader = $this->downloader;
        $config     = $this->config;
        $stub       = $this->getStub(['downloadVersionFile'])->getMock();
        $json       = $this->json;

        file_put_contents($jsonFile = sys_get_temp_dir().'/test.json', $json, LOCK_EX);

        $config->expects($this->once())
            ->method('getVersionUrl')
            ->willReturn('some-url')
        ;

        $downloader->expects($this->once())
            ->method('run')
            ->with('some-url', $jsonFile)
            ->willReturn($jsonFile)
        ;

        $return = $stub->downloadVersionFile($jsonFile, 'some-url');

        $this->assertContains('some-version', $return);
    }

    public function testExecute()
    {
        $stub   = $this->getStub();
        $app    = $this->createMock(Application::class);
        $cc     = $this->createMock(ClearCacheCommand::class);
        $logger = $this->logger;

        $stub = $stub
            ->getMock()
        ;

        $app->expects($this->once())
            ->method('get')
            ->with('clear-cache')
            ->willReturn($cc);

        $cc->expects($this->once())
            ->method('run');

        $stub->expects($this->once())
            ->method('getApplication')
            ->willReturn($app);

        $stub->expects($this->exactly(2))
            ->method('validateVersion')
            ->willReturnOnConsecutiveCalls(false, true);

        $stub->expects($this->once())
            ->method('update');

        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('latest version'));

        $stub->run($this->input, $this->output);
        $stub->run($this->input, $this->output);
    }

    public function testValidateVersion()
    {
        $logger = $this->logger;

        $stub = $this->getStub(['validateVersion'])->getMock();

        $json = $this->json;

        $json = json_decode($json, true);
        $logger->expects($this->exactly(6))
            ->method('info')
            ->withConsecutive(
                // for success testing
                ['start checking new version', []],
                ['no stable version found', []],
                [$this->stringContains('checking nightly build version'), []],

                // for failed testing
                ['start checking new version', []],
                ['no stable version found', []],
                [$this->stringContains('checking nightly build version'), []]
            );

        $stub->expects($this->exactly(4))
            ->method('downloadVersionFile')
            ->willReturnOnConsecutiveCalls(
                // for success testing
                false,
                $json,
                // for exception testing
                false,
                false
            );
        $stub->validateVersion();

        $this->expectException(\RuntimeException::class);
        $stub->validateVersion();
    }

    private function getStub($additionalExceptMethod = [])
    {
        $except = array_merge(['setName', 'setAliases', 'setDescription', 'run'], $additionalExceptMethod);
        $stub   = $this->getMockBuilder(SelfUpdateCommand::class)
            ->setMethodsExcept($except)
            ->setConstructorArgs([$this->downloader, $this->config, $this->fs, $this->logger])
        ;

        return $stub;
    }
}
