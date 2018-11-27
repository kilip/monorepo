<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
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

    public function setUp()
    {
        $this->resetOutput();

        $this->logger = new Logger($this->getOutput());
    }

    public function getTestSetConfigFile()
    {
        return array(
            // root 1 tests
            array('getBranches', 'root1', array('master', 'develop')),
            array('getTarget', 'root1', 'root1/.git'),

            // root 2 tests
            array('getBranches', 'root2', 'master'),
            array('getIgnoredTags', 'root2', 'v1.0.*'),
            array('getTarget', 'root2', 'root2/.git'),
        );
    }

    /**
     * @param $method
     * @param $expected
     * @dataProvider getTestSetConfigFile
     */
    public function testSetConfigFile($method, $project, $expected)
    {
        $tmpDir = sys_get_temp_dir().'/monorepo/tests';
        @mkdir($tmpDir, 0777, true);

        $json = <<<EOC
{
    "root1": {
        "target": "{$tmpDir}/root1/.git",
        "prefixes": {
            "src/sub1": "{$tmpDir}/sub1/.git",
            "src/sub2": "{$tmpDir}/sub2/.git"
        },
        "branches": ["develop","master"]
    },
    "root2": {
        "target": "{$tmpDir}/root2/.git",
        "prefixes": {
            "lib/sub1": "{$tmpDir}/sub1/.git",
            "lib/sub2": "{$tmpDir}/sub2/.git"
        },
        "ignored-tags": "v1.0.*"
    }
}
EOC;

        $file = $tmpDir.'/test1.json';
        file_put_contents($file, $json, LOCK_EX);

        $config = new Config($this->logger);
        $config->setConfigFile($file);

        $project = $config->getProject($project);
        $return = call_user_func_array(array($project, $method), array($project));

        if (!is_array($expected)) {
            $expected = array($expected);
        }

        foreach ($expected as $item) {
            $this->assertContains($item, $return);
        }
    }
}
