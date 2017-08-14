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
 * A Git binary wrapper.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Wrapper
{
    private $workingDirectory;

    public function __construct(string $path)
    {
        $this->workingDirectory = $path;
    }

    /**
     * Get the directory where commands are run.
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * Run a Git command wth the specified arguments.
     */
    public function run(string $command, array $arguments = []): CommandContext
    {
        $command = \array_merge(['git', $command], $arguments);

        $process = new Process($command);
        $process->setWorkingDirectory($this->workingDirectory)
            ->mustRun();

        return CommandContext::createFromProcess($process);
    }

    /**
     * Provide Git commands as methods to this class.
     *
     * Commands which use dashes in their name must be called using
     * _camelCase_. `git ls-files` becomes `$git->lsFiles();`.
     */
    public function __call(string $name, array $arguments)
    {
        // Convert camelCase name to kebab-case.
        $name = \strtolower(\preg_replace('/(?<!^)[[:upper:]]/', '-$0', $name));

        return $this->run($name, $arguments);
    }
}
