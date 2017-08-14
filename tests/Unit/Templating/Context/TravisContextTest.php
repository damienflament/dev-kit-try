<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Templating\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Templating\Context\TravisContext;

/**
 * The Travis CI context tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class TravisContextTest extends TestCase
{
    private $context;

    public function setUp(): void
    {
        \putenv('TRAVIS=true');

        $this->context = new TravisContext();
    }

    public function provideOnBuildMachine(): array
    {
        return [
            'a Travis CI build machine' => ['TRAVIS=true', true],
            'a development machine' => ['TRAVIS=false', false],
        ];
    }

    /**
     * It should check if it is running on a Travis CI build machine.
     *
     * @dataProvider provideOnBuildMachine
     */
    public function testOnBuildMachine(string $environment, bool $expected): void
    {
        \putenv($environment);
        $this->assertSame($expected, TravisContext::onBuildMachine());
    }

    /**
     * It should throw an exception when the Travis environment variables are
     * not found.
     *
     * @depends testOnBuildMachine
     */
    public function testThrowExceptionWhenNotOnBuildMachine(): void
    {
        \putenv('TRAVIS=false');

        $this->assertFalse(TravisContext::onBuildMachine());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Travis environment variables not found');

        new TravisContext();
    }

    /**
     * It should be named 'travis'.
     */
    public function testGetName(): void
    {
        $this->assertSame('travis', $this->context->getName());
    }

    public function provideGetParameters(): array
    {
        return [
            'a commit' => [
                [
                    'REPO_SLUG' => 'foo/bar',
                    'COMMIT_MESSAGE' => <<<_MESSAGE_
lorem ipsum
\x20
dolor sit amet

consectetur adipiscing elit
_MESSAGE_
                    ,
                ],
                [
                    'repository' => 'foo/bar',
                    'commit' => [
                        'title' => 'lorem ipsum',
                        'body' => <<<_BODY_
dolor sit amet

consectetur adipiscing elit
_BODY_
                        ,
                    ],
                ],
            ],
            'a commit without body' => [
                [
                    'REPO_SLUG' => 'foo/bar',
                    'COMMIT_MESSAGE' => 'lorem ipsum',
                ],
                [
                    'repository' => 'foo/bar',
                    'commit' => [
                        'title' => 'lorem ipsum',
                        'body' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * It should give parameters related to the build machine environment.
     *
     * @dataProvider provideGetParameters
     */
    public function testGetParameters(array $environment, array $expected): void
    {
        foreach ($environment as $name => $value) {
            \putenv("TRAVIS_${name}=${value}");
        }

        $this->assertSame($expected, $this->context->getParameters());
    }
}
