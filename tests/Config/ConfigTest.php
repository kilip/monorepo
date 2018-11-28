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

namespace MonorepoTest\Config;

use Monorepo\Config\Config;
use Monorepo\Console\Logger;
use Monorepo\Event\EventDispatcher;
use Monorepo\Test\JsonConfigFileTrait;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Config\Config
 * @covers \Monorepo\Config\Project
 */
class ConfigTest extends TestCase
{
    use OutputTrait, JsonConfigFileTrait;

    /**
     * @var MockObject
     */
    private $dispatcher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $target;

    /**
     * @var string
     */
    private $tmpDir;

    public function setUp()
    {
        $this->resetOutput();
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;
        $logger             = new Logger($this->getOutput());
        $this->tmpDir       = sys_get_temp_dir().'/monorepo/tests';
        $this->configureTarget($dispatcher, $logger);
    }

    public function getTestParseFile()
    {
        return [
            // root 1 tests
            ['getBranches', 'root1', ['master', 'develop']],
            ['getOrigin', 'root1', 'root1/.git'],
            ['getName', 'root1', 'root1'],

            // root 2 tests
            ['getBranches', 'root2', 'master'],
            ['getIgnoredTags', 'root2', 'v1.0.*'],
            ['getOrigin', 'root2', 'root2/.git'],
            ['getName', 'root2', 'root2'],
        ];
    }

    public function testGetProjects()
    {
        $config = $this->target;

        $this->assertEmpty($config->getProjects());

        $config->parseFile($this->generateJsonConfig1());
        $projects = $config->getProjects();
        $this->assertCount(2, $projects);
        $this->assertArrayHasKey('root1', $projects);
        $this->assertArrayHasKey('root2', $projects);
    }

    public function testGetProjectThrowsException()
    {
        $config = $this->target;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project "foo" not exist.');

        $config->parseFile($this->generateJsonConfig1());
        $config->getProject('foo');
    }

    /**
     * @param $method
     * @param $expected
     * @dataProvider getTestParseFile
     */
    public function testParseFile($method, $project, $expected)
    {
        $file   = $this->generateJsonConfig1();
        $config = $this->target;

        $config->parseFile($file);

        $project = $config->getProject($project);
        $return  = \call_user_func_array([$project, $method], [$project]);

        if (!\is_array($expected)) {
            $expected = [$expected];
        }

        foreach ($expected as $item) {
            $this->assertContains($item, $return);
        }
    }

    /**
     * @TODO: Add more tests
     */
    public function testProjectPrefixes()
    {
        $config = $this->target;

        $config->parseFile($this->generateJsonConfig1());

        $prefixes = $config->getProject('root1')->getPrefixes();
        $this->assertCount(2, $prefixes);
    }

    public function testSetConfigThrowsWhenFileNotExists()
    {
        $config = $this->target;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config file "foo" is not exist or unreadable.');

        $config->parseFile('foo');
    }

    public function testSetConfigThrowsWithInvalidJson()
    {
        $config = $this->target;

        $file = $this->getTempDir().'/foo.json';
        file_put_contents($file, '{"foo":}', LOCK_EX);

        $this->expectException(\InvalidArgumentException::class);
        $config->parseFile($file);
    }

    public function testValidate()
    {
        $json = <<<'JSON'
[
    {
        "name": "project1",
        "origin": "git@github.com",
        "prefixes": [
            {
                "key": "src/foo",
                "target": "foo"
            },
            {
                "key": "src/bar",
                "target": "bar"
            }
         ],
         "ignore-tags": "v1.0.*"
    }
]
JSON;

        $config = $this->target;
        $config->parse($json);

        $project = $config->getProject('project1');

        $this->assertEquals('project1', $project->getName());
    }

    private function configureTarget($dispatcher, $logger)
    {
        $this->target     = new Config($dispatcher, $logger);
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;

        return $this->target;
    }
}
