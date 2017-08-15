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

use Symfony\Component\Filesystem\Filesystem;

/**
 * A Git working copy.
 *
 * This class does not aim to be a complete wrapper. Only features used in the
 * current application are implemented.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class WorkingCopy
{
    const STATUS_NEW = 'new';
    const STATUS_DELETED = 'deleted';
    const STATUS_MODIFIED = 'modified';

    private $git;

    /**
     * Create a working copy in the specified directory using the given
     * remote URL.
     *
     * If the directory does not exists, the remote repository is cloned.
     * Otherwise, changes are pulled.
     */
    public static function createFromRemote(string $directory, string $url)
    {
        $git = new Wrapper($directory);
        $fs = new Filesystem();

        if (!$fs->exists($directory)) {
            $git->clone($url, $directory);
        }

        $workingCopy = new static($directory);

        $workingCopy->checkoutRemoteBranch('master');
        $workingCopy->pull();

        return $workingCopy;
    }

    public function __construct(string $path)
    {
        $this->git = new Wrapper($path);
    }

    /**
     * Get the directory where the working copy is located.
     */
    public function getDirectory(): string
    {
        return $this->git->getWorkingDirectory();
    }

    /**
     * Set the author name and email address.
     */
    public function setAuthor(string $name, string $email): void
    {
        $this->git->config('--local', 'user.name', $name);
        $this->git->config('--local', 'user.email', $email);
    }

    /**
     * Check if the specified branch exists in the remote repository.
     */
    public function hasRemoteBranch(string $name): bool
    {
        return $this->hasReference("remotes/origin/${name}");
    }

    /**
     * Check out the sprecified branch.
     *
     * If it does not exists on the remote, it is created in the local
     * repository from the remote from _master_.
     */
    public function checkoutRemoteBranch(string $name): void
    {
        if ($this->hasRemoteBranch($name)) {
            $this->git->checkout($name, '--');
        } else {
            $this->git->checkout('-b', $name);
            $this->git->push('--set-upstream', 'origin', $name);
        }
    }

    /**
     * Check if changes occured since the last commit.
     */
    public function hasChanges(): bool
    {
        return !empty($this->getChanges());
    }

    /**
     * Get the status of files on which changes occured.
     *
     * An associative array is returned where the key is the filename and the
     * value is one of the `STATUS_*` constant.
     *
     * Both tracked and untracked files are returned.
     */
    public function getChanges(): array
    {
        $filesStatus = [];

        $lines = $this->git->status('--porcelain', '--untracked-files=all')
            ->getOutputLines();

        foreach ($lines as $line) {
            [$statusFlag, $filename] = \explode("\x20", $line);

            switch ($statusFlag) {
                case '??':
                case 'A':
                    $status = self::STATUS_NEW;

                    break;

                case 'D':
                    $status = self::STATUS_DELETED;

                    break;

                case 'M':
                    $status = self::STATUS_MODIFIED;

                    break;

                default:
                    throw new \UnexpectedValueException(\sprintf(
                        'The status flag "%s" is not handled.',
                        $statusFlag
                    ));
            }

            $filesStatus[$filename] = $status;
        }

        \ksort($filesStatus, SORT_NATURAL | SORT_FLAG_CASE);

        return $filesStatus;
    }

    /**
     * Get the list of files on which changes occured.
     */
    public function getChangedFiles(): array
    {
        return \array_keys($this->getChanges());
    }

    /**
     * Get the list of files for which content has been modified.
     */
    public function getModifiedFiles(): array
    {
        return $this->getFilesByStatus(self::STATUS_MODIFIED);
    }

    /**
     * Get the list of new files.
     *
     * Both tracked and untracked files are returned.
     */
    public function getNewFiles(): array
    {
        return $this->getFilesByStatus(self::STATUS_NEW);
    }

    /**
     * Get the diff of an uncommited file.
     *
     * The file context lines are ignored.
     */
    public function getDiffByFile(string $filename, bool $withColors = true): string
    {
        $colorsFlag = 'always';

        if (!$withColors) {
            $colorsFlag = 'never';
        }

        $this->git->add('--intent-to-add', $filename);
        $diff = $this->git->diff('--patience', "--color=${colorsFlag}", '--', $filename)
            ->getOutputLines();
        $this->git->reset('--', $filename);

        $contentDiff = \implode(PHP_EOL, \array_slice($diff, 4));

        return $contentDiff;
    }

    /**
     * Reset all changes.
     *
     * Changes on untracked files are resetted as well.
     */
    public function reset(): void
    {
        $this->git->reset('--hard');
        $this->git->clean('-d', '--force');
    }

    /**
     * Commit all changes using the given message.
     */
    public function commit(string $message): void
    {
        $this->git->add('--all', '.');
        $this->git->commit('--message', $message);
    }

    /**
     * Pull the changes from the remote.
     */
    public function pull(): void
    {
        $this->git->pull();
    }

    /**
     * Push to the configured remote for the current branch.
     */
    public function push(): void
    {
        $this->git->push();
    }

    private function hasReference(string $path): bool
    {
        return !empty($this->git->forEachRef("refs/${path}")
            ->getOutput());
    }

    private function getFilesByStatus(string $status): array
    {
        $changedFiles = $this->getChanges();

        $files = \array_filter($changedFiles, function (string $fileStatus) use ($status) {
            return $status === $fileStatus;
        });

        return \array_keys($files);
    }
}
