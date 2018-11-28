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

use Monorepo\Processor\Filesystem;
use Monorepo\Test\OutputTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem as Executor;

class TestFilesystem extends Filesystem
{
    public function setExecutor(Executor $executor)
    {
        $this->executor = $executor;
    }
}

/**
 * Class FilesystemTest.
 *
 * @author Anthonius Munthi <https://itstoni.com>
 * @covers \Monorepo\Processor\Filesystem
 * @TODO: add tests for all filesystem method
 */
class FilesystemTest extends TestCase
{
    use OutputTrait;

    public function getTestOperation()
    {
        return [
            ['mkdir', ['some-dir', 0755], 'mkdir some-dir mode: 755'],
            ['copy', ['origin', 'target'], 'copy from origin to target'],
            ['touch', ['file', '$time', '$atime'], 'touch file time: $time atime: $atime'],
        ];
    }

    public function testMkdir()
    {
        $dir = sys_get_temp_dir().'/testdir';
        $fs  = new Filesystem($this->getOutput());

        $fs->mkdir($dir);

        $display = $this->getDisplay();
        $this->assertDirectoryExists($dir);
        $this->assertContains('mkdir', $display);
        $this->assertContains($dir, $display);
    }

    /**
     * @param string $operation
     * @param array  $arguments
     * @param string $expected
     * @dataProvider getTestOperation
     */
    public function testOperation($operation, $arguments, $expected)
    {
        $output   = $this->getOutput();
        $executor = $this->createMock(Executor::class);
        $fs       = new TestFilesystem($output);

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

        $executor->expects($this->once())
            ->method($operation)
            ->willReturn('some value')
        ;
        $fs->setExecutor($executor);

        $return = \call_user_func_array([$fs, $operation], $arguments);

        $display = $this->getDisplay();
        $this->assertEquals('some value', $return);
        $this->assertContains($expected, $display);
    }
}
