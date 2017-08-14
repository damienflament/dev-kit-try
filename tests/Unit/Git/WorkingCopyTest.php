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
use Symfony\Cmf\DevKit\Git\WorkingCopy;
use Symfony\Cmf\DevKit\Git\Wrapper as Git;
use Symfony\Cmf\DevKit\Tests\Fixtures\Git as GitFixtures;

/**
 * The Git working copy tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class WorkingCopyTest extends TestCase
{
    private $fixtures;
    private $git;
    private $workingCopy;
    private $remoteGit;
    private $remoteWorkingCopy;

    public function setUp(): void
    {
        $this->fixtures = new GitFixtures();

        $this->fixtures->createRepositories([
            'remote' => [
                'master' => [
                    '.gitignore' => 'ignored',
                    'empty' => '',
                    'foo' => 'bar',
                ],
                'foo' => [],
            ],
            'local' => 'remote',
        ]);

        $localRepositoryPath = $this->fixtures->getRepositoryPath('local');
        $remoteRepositoryPath = $this->fixtures->getRepositoryPath('remote');

        $this->git = new Git($localRepositoryPath);
        $this->workingCopy = new WorkingCopy($localRepositoryPath);
        $this->remoteGit = new Git($remoteRepositoryPath);
        $this->remoteWorkingCopy = new WorkingCopy($remoteRepositoryPath);
    }

    public function provideCreateFromRemote(): array
    {
        return [
            'a new working copy' => [
                'new-from-remote',
            ],
            'an existing working copy' => [
                'local',
            ],
        ];
    }

    /**
     * It should create a {@see WorkingCopy} at the specified path from a
     * remote repository.
     *
     * @dataProvider provideCreateFromRemote
     */
    public function testCreateFromRemote(string $repository): void
    {
        $localPath = $this->fixtures->getRepositoryPath($repository);
        $remotePath = $this->fixtures->getRepositoryPath('remote');

        $createdWorkingCopy = WorkingCopy::createFromRemote($localPath, $remotePath);
        $expectedWorkingCopy = new WorkingCopy($localPath);

        $this->assertEquals($expectedWorkingCopy, $createdWorkingCopy);
    }

    /**
     * It should ensure the _master_ branch is checked out when creating a
     * {@see WorkingCopy} on an existing directory.
     */
    public function testCreateFromRemoteEnsureMasterBranch(): void
    {
        $localPath = $this->fixtures->getRepositoryPath('local');
        $remotePath = $this->fixtures->getRepositoryPath('remote');

        $this->git->checkout('foo', '--');

        $workingCopy = WorkingCopy::createFromRemote($localPath, $remotePath);

        $this->assertSame('master', $this->getCurrentBranch());
    }

    /**
     * It should give the directory where the working copy is locatedd.
     */
    public function testGetDirectory(): void
    {
        $this->assertSame(
            $this->fixtures->getRepositoryPath('local'),
            $this->workingCopy->getDirectory()
        );

        $this->assertSame(
            $this->fixtures->getRepositoryPath('remote'),
            $this->remoteWorkingCopy->getDirectory()
        );
    }

    /**
     * It should set the author name and email.
     */
    public function testSetAuthor(): void
    {
        $name = 'foo';
        $email = 'foo@bar.baz';

        $this->workingCopy->setAuthor($name, $email);

        $this->assertSame($name, $this->getConfig('user.name'));
        $this->assertSame($email, $this->getConfig('user.email'));
    }

    public function provideRemoteBranch(): array
    {
        return [
            'an existing branch' => ['foo', true],
            'a non existing branch' => ['not-existing-branch', false],
        ];
    }

    /**
     * It should check if a remote branch exists.
     *
     * @dataProvider provideRemoteBranch
     */
    public function testHasRemoteBranch(string $branch, bool $shouldExist): void
    {
        $this->assertSame($shouldExist, $this->workingCopy->hasRemoteBranch($branch));
    }

    /**
     * It should check out the specified branch. If this branch does not exist
     * on the remote repository, it should be created.
     *
     * @dataProvider provideRemoteBranch
     */
    public function testCheckoutRemoteBranch(string $branch, bool $shouldExist): void
    {
        $this->assertSame('master', $this->getCurrentBranch());
        $this->assertSame($shouldExist, $this->workingCopy->hasRemoteBranch($branch));

        $this->workingCopy->checkoutRemoteBranch($branch);

        $this->assertSame($branch, $this->getCurrentBranch());
        $this->assertTrue($this->workingCopy->hasRemoteBranch($branch));
    }

    public function provideGetChanges(): array
    {
        return [
            'not any modified files' => [
                [],
                [],
            ],
            'a modified file' => [
                [
                    'foo' => 'bar baz',
                ],
                [
                    'foo' => WorkingCopy::STATUS_MODIFIED,
                ],
            ],
            'a deleted file' => [
                [
                    'foo' => null,
                ],
                [
                    'foo' => WorkingCopy::STATUS_DELETED,
                ],
            ],
            'an untracked file' => [
                [
                    'untracked' => '',
                ],
                [
                    'untracked' => WorkingCopy::STATUS_NEW,
                ],
            ],
            'an ignored file' => [
                [
                    'ignored' => '',
                ],
                [],
            ],
            'a modified, a deleted, an untracked and an ignored file' => [
                [
                    'empty' => 'content',
                    'foo' => null,
                    'untracked' => '',
                    'ignored' => '',
                ],
                [
                    'empty' => WorkingCopy::STATUS_MODIFIED,
                    'foo' => WorkingCopy::STATUS_DELETED,
                    'untracked' => WorkingCopy::STATUS_NEW,
                ],
            ],
        ];
    }

    /**
     * It should give the list of changed files sorted alphabetically
     * regardless their tracking state.
     *
     * @dataProvider provideGetChanges
     */
    public function testGetChanges(array $modifications, array $expectedChanges): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectedChanges, $this->workingCopy->getChanges());
    }

    public function provideHasChanges(): array
    {
        $datasets = $this->provideGetChanges();

        return \array_map(function (array $dataset) {
            [$modifiedFiles, $expectedChanges] = $dataset;

            return [
                $modifiedFiles,
                !empty($expectedChanges),
            ];
        }, $datasets);
    }

    /**
     * It should check if changes ocurred regardless the tracking state.
     *
     * @dataProvider provideHasChanges
     */
    public function testHasChanges(array $modifications, bool $expectsChanges): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectsChanges, $this->workingCopy->hasChanges());
    }

    public function provideGetChangedFiles(): array
    {
        $datasets = $this->provideGetChanges();

        return \array_map(function (array $dataset) {
            [$modifications, $expectedChanges] = $dataset;

            return [
                $modifications,
                \array_keys($expectedChanges),
            ];
        }, $datasets);
    }

    /**
     * It should give the list of changed files sorted alphabetically
     * regardless their tracking state.
     *
     * @dataProvider provideGetChangedFiles
     */
    public function testGetChangedFiles(array $modifications, array $expectedFiles): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectedFiles, $this->workingCopy->getChangedFiles());
    }

    public function provideGetModifiedFiles(): array
    {
        $datasets = $this->provideGetChanges();

        return \array_map(function (array $dataset) {
            [$modifications, $expectedChanges] = $dataset;

            $expectedFiles = \array_keys(\array_filter($expectedChanges, function (string $status) {
                return WorkingCopy::STATUS_MODIFIED === $status;
            }));

            return [$modifications, $expectedFiles];
        }, $datasets);
    }

    /**
     * It should give the list of files for which content has been modified
     * sorted alphabetically.
     *
     * @dataProvider provideGetModifiedFiles
     */
    public function testGetModifiedFiles(array $modifications, array $expectedFiles): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectedFiles, $this->workingCopy->getModifiedFiles());
    }

    public function provideGetNewFiles(): array
    {
        $datasets = $this->provideGetChanges();

        return \array_map(function (array $dataset) {
            [$modifications, $expectedChanges] = $dataset;

            $expectedFiles = \array_keys(\array_filter($expectedChanges, function (string $status) {
                return WorkingCopy::STATUS_NEW === $status;
            }));

            return [$modifications, $expectedFiles];
        }, $datasets);
    }

    /**
     * It should give the list of new files sorted alphabetically
     * regardless their tracking state.
     *
     * @dataProvider provideGetNewFiles
     */
    public function testGetNewFiles(array $modifications, array $expectedFiles): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectedFiles, $this->workingCopy->getNewFiles());
    }

    public function provideGetDiffByFile(): array
    {
        return [
            'a modified file' => [
                [
                    'foo' => 'bar baz',
                ],
                'foo',
                <<<_DIFF_
@@ -1 +1 @@
-bar
\ No newline at end of file
+bar baz
\ No newline at end of file
_DIFF_
            ],
            'a deleted file' => [
                [
                    'foo' => null,
                ],
                'foo',
                '',
            ],
            'an untracked file' => [
                [
                    'untracked' => 'foo',
                ],
                'untracked',
                <<<_DIFF_
@@ -0,0 +1 @@
+foo
\ No newline at end of file
_DIFF_
            ],
        ];
    }

    /**
     * It should give the diff of an uncommited file.
     *
     * @dataProvider provideGetDiffByFile
     */
    public function testGetDiffByFile(array $modifications, string $filename, string $expectedDiff): void
    {
        $this->fixtures->modifyFiles('local', $modifications);

        $this->assertSame($expectedDiff, $this->workingCopy->getDiffByFile($filename, false));
    }

    /**
     * It should reset changes on files regardless their tracking state.
     */
    public function testReset(): void
    {
        $this->fixtures->modifyFiles('local', [
            'empty' => 'content',
            'foo' => null,
            'untracked' => '',
            'untracked directory' => [
                'untracked' => '',
            ],
        ]);
        $this->assertTrue($this->workingCopy->hasChanges());

        $this->workingCopy->reset();
        $this->assertFalse($this->workingCopy->hasChanges());
    }

    /**
     * It should commit changes using the specified message.
     */
    public function testCommit(): void
    {
        $this->fixtures->modifyFiles('local', [
            'foo' => 'bar baz',
        ]);

        $this->workingCopy->commit('Modified files');

        $lastCommitMessage = $this->git->log('--format=%B', '-1')->getOutput();

        $this->assertFalse($this->workingCopy->hasChanges());
        $this->assertSame('Modified files', $lastCommitMessage);
    }

    /**
     * It should pull the changes from the remote on the current branch.
     */
    public function testPull(): void
    {
        $this->fixtures->modifyFiles('remote', [
            'foo' => 'bar baz',
        ]);

        $this->remoteWorkingCopy->commit('Modified files');

        $remoteLastCommitHash = $this->remoteGit->revParse('HEAD')->getOutput();
        $localLastCommitHash = $this->git->revParse('HEAD')->getOutput();

        $this->assertNotSame($remoteLastCommitHash, $localLastCommitHash);

        $this->workingCopy->pull();

        $remoteLastCommitHash = $this->remoteGit->revParse('HEAD')->getOutput();
        $localLastCommitHash = $this->git->revParse('HEAD')->getOutput();

        $this->assertSame($remoteLastCommitHash, $localLastCommitHash);
    }

    public function testPush(): void
    {
        $this->fixtures->modifyFiles('local', [
            'foo' => 'bar baz',
        ]);

        $this->workingCopy->commit('Modified files');

        $remoteLastCommitHash = $this->remoteGit->revParse('HEAD')->getOutput();
        $localLastCommitHash = $this->git->revParse('HEAD')->getOutput();

        $this->assertNotSame($remoteLastCommitHash, $localLastCommitHash);

        $this->workingCopy->push();

        $remoteLastCommitHash = $this->remoteGit->revParse('HEAD')->getOutput();
        $localLastCommitHash = $this->git->revParse('HEAD')->getOutput();

        $this->assertSame($remoteLastCommitHash, $localLastCommitHash);
    }

    private function getConfig(string $name): string
    {
        return $this->git->config('--local', $name)
            ->getOutput();
    }

    private function getCurrentBranch(): string
    {
        return $this->git->symbolicRef('--short', 'HEAD')->getOutput();
    }
}
