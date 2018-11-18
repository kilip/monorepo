<?php

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonorepoTest\Command;

use Monorepo\Command\SplitCommand;
use Monorepo\Test\CommandTestCase;
use MonorepoTest\Fixtures;

class TestSplitCommand
{
}

class SplitCommandTest extends CommandTestCase
{
    private static $cwd;
    /**
     * @var Fixtures
     */
    private $fixtures;

    public static function setUpBeforeClass()
    {
        static::$cwd = getcwd();
    }

    public static function tearDownAfterClass()
    {
        chdir(static::$cwd);
    }

    public function setUp()
    {
    }

    public function testRunCommand()
    {
        $tempdir = sys_get_temp_dir().'/monorepo';
        $splitDir = sys_get_temp_dir().'/monorepo/split';
        $fixtures = new Fixtures(__DIR__.'/../fixtures/origin1');
        $json = $this->json1($fixtures->getTarget());

        $fixtures->initGit();
        $fixtures->commit();
        $fixtures->execute('git checkout -b develop');

        $fixtures->initSplit($foo = $splitDir.'/foo');
        $fixtures->initSplit($hello = $splitDir.'/hello');

        file_put_contents($configFile = $tempdir.'/monorepo.json', $json, LOCK_EX);

        $this->assertFileExists($configFile);
        $command = new SplitCommand();
        $tester = $this->getCommandTester($command);
        $tester->run(array(
            'split',
            '--config' => $configFile,
        ));

        chdir($fixtures->getTarget());
        $display = $tester->getDisplay();

        $fixtures->execute('git checkout master', $foo);
        $fixtures->execute('git checkout master', $hello);
        $this->assertContains('split/foo', $display);
        $this->assertContains('split/hello', $display);
        $this->assertFileExists($foo.'/Bar.php');
        $this->assertFileExists($hello.'/World.php');
    }

    private function json1($path)
    {
        $temp = sys_get_temp_dir().'/monorepo/split';
        $json = <<<EOC
{
    "test-monorepo": {
        "prefixes": [
            {
                "key": "src/foo",
                "target": "$temp/foo/.git"
            },
            {
                "key": "src/hello",
                "target": "$temp/hello/.git"
            }
        ],
        "branches": ["master"],
        "path": "$path"
    }
}
EOC;

        return $json;
    }
}
