<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Fixtures;

use Symfony\Cmf\DevKit\Git\Wrapper;

/**
 * Create Git fixtures for unit tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Git
{
    private $filesystem;

    public function __construct()
    {
        $reflection = new \ReflectionClass($this);
        $namespace = $reflection->getShortName();

        $this->filesystem = new Filesystem($namespace);
    }

    public function getRepositoryPath(string $name): string
    {
        if (false !== \strpos($name, '/')) {
            throw new \InvalidArgumentException('A repository name must no contain any "/" character.');
        }

        return $this->filesystem->getFilePath("/$name");
    }

    /**
     * Create filesystem fixtures following the specified structure.
     *
     * The structure is an associative array where the key is the
     * _repository name_ and the value the _specification_.
     *
     * Allowed specifications are:
     *  - a `string`: the repository is a clone of the specified repository,
     *  - an **associative** `array`: the repository has the specified branches
     *    with the specified filesystem structure.
     *    See {@see Filesystem::createFiles}.
     */
    public function createRepositories(array $structure): void
    {
        $clones = [];

        foreach ($structure as $repository => $specification) {
            $type = \gettype($specification);

            switch ($type) {
                // new repository
                case 'array':
                    $this->filesystem->createFiles([$repository => [
                        '.gitignore' => '',
                    ]]);

                    $this->initializeRepository($repository, $specification);

                    break;

                // cloned repository
                case 'string':
                    // set repository for cloning
                    $clones[$repository] = $specification;

                    break;

                // not handled specification
                default:
                    throw new \InvalidArgumentException(\sprintf(
                        'Specification of type "%s" not handled.',
                        $type
                    ));

                    break;
            }
        }

        // Clone repositories
        foreach ($clones as $repository => $remote) {
            $this->cloneRepository($repository, $remote);
        }
    }

    /**
     * Modify the filesystem structure of the specified repository.
     */
    public function modifyFiles(string $repository, array $structure): void
    {
        $this->filesystem->createFiles([$repository => $structure]);
    }

    /**
     * Initialize the repository with the specified branches.
     */
    private function initializeRepository(string $repository, array $branches): void
    {
        $path = static::getRepositoryPath($repository);
        $git = new Wrapper($path);

        $git->init();
        $git->add('.');
        $git->commit('--message', 'Initialized repository');

        foreach ($branches as $branch => $structure) {
            $git->checkout('-B', $branch);

            if (\count($structure) > 0) {
                $this->filesystem->createFiles([$repository => $structure]);
                $git->add('.');
                $git->commit('--message', 'Initialized branch');
            }
        }

        $git->checkout('master');
    }

    /**
     * Clone the specified repository from the specified remote.
     */
    private function cloneRepository(string $repository, string $remote): void
    {
        $path = static::getRepositoryPath($repository);
        $remotePath = static::getRepositoryPath($remote);
        $git = new Wrapper($path);
        $remoteGit = new Wrapper($remotePath);

        $remoteGit->config('receive.denyCurrentBranch', 'updateInstead');

        $git->clone($remotePath, $path);
    }
}
