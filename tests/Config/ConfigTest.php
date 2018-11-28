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
use Monorepo\Test\OutputTrait;
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
    use OutputTrait;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $tmpDir;

    public function setUp()
    {
        $this->resetOutput();

        $this->logger = new Logger($this->getOutput());
        $this->tmpDir = sys_get_temp_dir().'/monorepo/tests';
    }

    /**
     * @return string Generated json config file name
     */
    public function generateJsonConfig1()
    {
        $tmpDir = $this->tmpDir;
        $json   = <<<'JSON'
[
    {
        "name": "root1",
        "origin": "{$tmpDir}/root1/.git",
        "prefixes": [
            {
                "key": "src/sub1",
                "target": "{$tmpDir}/sub1/.git" 
            },
            {
                "key": "src/sub2",
                "target": "{$tmpDir}/sub2/.git"
            }
        ],
        "branches": ["develop","master"]
    },
    {
        "name": "root2",
        "origin": "{$tmpDir}/root2/.git",
        "prefixes": [
            {
                "key": "lib/sub1",
                "target": "{$tmpDir}/sub1/.git"
            },
            {
                "key": "lib/sub2",
                "target": "{$tmpDir}/sub2/.git"
            }
        ],
        "ignored-tags": "v1.0.*"
    }
]
JSON;

        $file   = $tmpDir.'/test1.json';
        $tmpDir = sys_get_temp_dir().'/monorepo/tests';
        @mkdir($tmpDir, 0777, true);
        file_put_contents($file, $json, LOCK_EX);

        return $file;
    }

    public function getTestParseFile()
    {
        return array(
            // root 1 tests
            array('getBranches', 'root1', array('master', 'develop')),
            array('getOrigin', 'root1', 'root1/.git'),
            array('getName', 'root1', 'root1'),

            // root 2 tests
            array('getBranches', 'root2', 'master'),
            array('getIgnoredTags', 'root2', 'v1.0.*'),
            array('getOrigin', 'root2', 'root2/.git'),
            array('getName', 'root2', 'root2'),
        );
    }

    public function testGetProjects()
    {
        $config = new Config($this->logger);

        $this->assertEmpty($config->getProjects());

        $config->parseFile($this->generateJsonConfig1());
        $projects = $config->getProjects();
        $this->assertCount(2, $projects);
        $this->assertArrayHasKey('root1', $projects);
        $this->assertArrayHasKey('root2', $projects);
    }

    public function testGetProjectThrowsException()
    {
        $config = new Config($this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project "foo" not exist.');

        $config->parseFile($this->generateJsonConfig1());
        $config->getProject('foo');
    }

    /**
     * @param $method
     * @param $expected
     * @dataProvider getTestparseFile
     */
    public function testparseFile($method, $project, $expected)
    {
        $file   = $this->generateJsonConfig1();
        $config = new Config($this->logger);

        $config->parseFile($file);

        $project = $config->getProject($project);
        $return  = \call_user_func_array(array($project, $method), array($project));

        if (!\is_array($expected)) {
            $expected = array($expected);
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
        $config = new Config($this->logger);

        $config->parseFile($this->generateJsonConfig1());

        $prefixes = $config->getProject('root1')->getPrefixes();
        $this->assertCount(2, $prefixes);
    }

    public function testSetConfigThrowsWhenFileNotExists()
    {
        $config = new Config($this->logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config file "foo" is not exist or unreadable.');

        $config->parseFile('foo');
    }

    public function testSetConfigThrowsWithInvalidJson()
    {
        $config = new Config($this->logger);

        $file = $this->generateJsonConfig1();
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

        $config = new Config($this->logger);
        $config->parse($json);

        $project = $config->getProject('project1');

        $this->assertEquals('project1', $project->getName());
    }
}
