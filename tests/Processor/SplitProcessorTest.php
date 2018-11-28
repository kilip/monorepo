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

namespace MonorepoTest\Processor;

use Monorepo\Config\Config;
use Monorepo\Console\Logger;
use Monorepo\Event\EventDispatcher;
use Monorepo\Processor\Runner;
use Monorepo\Processor\SplitProcessor;
use Monorepo\Test\GitRepositoryTrait;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SplitProcessorTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Processor\SplitProcessor
 */
class SplitProcessorTest extends TestCase
{
    use OutputTrait, GitRepositoryTrait;

    /**
     * @var Logger
     */
    private $logger;

    private $tempDir;

    public function setUp()
    {
        $this->resetOutput();

        $this->logger = new Logger($this->getOutput());
        $this->getOutput()->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->tempDir = sys_get_temp_dir().'/monorepo';
    }

    public function testSplit()
    {
        $remote1 = $this->createRemoteFrom('remote1', __DIR__.'/../fixtures/origin1');
        $foo     = $this->createEmptyRemote('foo');
        $hello   = $this->createEmptyRemote('hello');
        $json    = <<<EOC
[
    {
        "name": "origin1", 
        "origin": "{$remote1}",
        "prefixes": [
            {
                "key": "src/foo",
                "target": "{$foo}"
            },
            {
                "key": "src/hello",
                "target": "{$hello}"
            }
        ]
    }
]
EOC;

        $configFile = $this->getTempDir().'/test1.json';
        file_put_contents($configFile, $json, LOCK_EX);

        $logger     = $this->logger;
        $input      = $this->createMock(InputInterface::class);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $runner     = new Runner($logger);
        $config     = new Config($dispatcher, $logger);

        $processor = new SplitProcessor($logger, $input, $runner, $config);

        $config->parseFile($configFile);
        $processor->onSplit();

        $display = $this->getDisplay();
        $this->assertContains('processing origin1', $display);
        $this->assertContains('processing branch master', $display);
    }
}
