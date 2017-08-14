<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Git;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Git\Wrapper;
use Symfony\Cmf\DevKit\Tests\Fixtures\Filesystem;

/**
 * The Git wrapper test.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class WrapperTest extends TestCase
{
    private $fixtures;
    private $wrapper;

    public function setUp(): void
    {
        $this->fixtures = new Filesystem();

        $this->fixtures->createFiles([
            'directory' => [],
        ]);

        $this->wrapper = new Wrapper($this->fixtures->getFilePath('/directory'));
    }

    /**
     * It should give its working directory.
     */
    public function testGetWorkingDirectory(): void
    {
        $this->assertSame(
            $this->fixtures->getFilePath('/directory'),
            $this->wrapper->getWorkingDirectory()
        );
    }

    /**
     * It should run the given command with arguments.
     */
    public function testRun(): void
    {
        $this->assertSame(
            \trim(`git help`),
            $this->wrapper->run('help')->getOutput()
        );

        $this->assertSame(
            \trim(`git help --guides`),
            $this->wrapper->run('help', ['--guides'])->getOutput()
        );
    }

    /**
     * It should make the commands available as methods.
     */
    public function test__call(): void
    {
        $this->assertEquals(
            $this->wrapper->run('help'),
            $this->wrapper->help()
        );

        $this->assertEquals(
            $this->wrapper->run('help', ['--guides']),
            $this->wrapper->help('--guides')
        );
    }
}
