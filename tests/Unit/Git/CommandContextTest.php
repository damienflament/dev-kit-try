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
use Symfony\Cmf\DevKit\Git\CommandContext;
use Symfony\Component\Process\Process;

/**
 * The Git command context tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class CommandContextTest extends TestCase
{
    /**
     * It should create a context from a process.
     */
    public function testCreateFromProcess(): void
    {
        $process = $this->prophesize(Process::class);
        $process->getOutput()
            ->willReturn('foo');

        $context = new CommandContext('foo');

        $contextFromFactory = CommandContext::createFromProcess($process->reveal());

        $this->assertEquals($context, $contextFromFactory);
    }

    public function provideGetOutput(): array
    {
        return [
            'an empty output' => [
                '',
                '',
            ],
            'a single whitespace' => [
                "\x20",
                '',
            ],
            ' a single newline' => [
                "\n",
                '',
            ],
            'a single word with trailing whitespaces' => [
                "\x20foo\x20",
                'foo',
            ],
            'multiple lines with empty ones and whitespaces' => [
                "\nfoo\x20\n\x20\n\nbar\nbaz\x20\x20",
                "foo\x20\n\x20\n\nbar\nbaz",
            ],
        ];
    }

    /**
     * It should give the cleaned output.
     *
     * @dataProvider provideGetOutput
     */
    public function testGetOutput(string $rawOutput, string $cleaneOutput): void
    {
        $context = new CommandContext($rawOutput);

        $this->assertSame($cleaneOutput, $context->getOutput());
    }

    public function provideGetOutputLines(): array
    {
        return [
            'an empty output' => [
                '',
                [],
            ],
            'a single word' => [
                'foo',
                ['foo'],
            ],
            'a single word with trailing whitespaces' => [
                "\x20\x20\x20foo\x20",
                ['foo'],
            ],
            'two lines' => [
                "foo\nbar",
                ['foo', 'bar'],
            ],
            'multiple lines with empty ones' => [
                "\nfoo\n\nbar\n\n\nbaz\n",
                ['foo', 'bar', 'baz'],
            ],
            'multiple lines with empty ones and whitespaces' => [
                "\nfoo\x20\n\x20\n\nbar\nbaz\x20\x20",
                ['foo', 'bar', 'baz'],
            ],
        ];
    }

    /**
     * It should give the output lines as a list.
     *
     * @dataProvider provideGetOutputLines
     */
    public function testGetOutputLines(string $output, array $expectedList): void
    {
        $context = new CommandContext($output);

        $this->assertSame($expectedList, $context->getOutputLines());
    }
}
