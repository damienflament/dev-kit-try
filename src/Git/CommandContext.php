<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Git;

use Symfony\Component\Process\Process;

/**
 * A Git command context.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class CommandContext
{
    private $output;

    /**
     * Create a context from the given process.
     */
    public static function createFromProcess(Process $process)
    {
        return new self($process->getOutput());
    }

    public function __construct(string $output)
    {
        $this->output = $output;
    }

    /**
     * Get the the output of the command.
     */
    public function getOutput(): string
    {
        return \trim($this->output);
    }

    /**
     * Get the lines outputed by the command.
     */
    public function getOutputLines(): array
    {
        $lines = \explode(PHP_EOL, $this->getOutput());
        $trimedLines = \array_map('trim', $lines);
        $filteredLines = \array_filter($trimedLines);
        $rebasedLines = \array_values($filteredLines);

        return $rebasedLines;
    }
}
