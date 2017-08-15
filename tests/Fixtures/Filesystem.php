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

use Symfony\Component\Filesystem\Filesystem as FilesystemUtils;

/**
 * Create filesystem fixtures for unit tests.
 *
 * As the fixtures are deleted when the instance is destructed, a reference
 * must be kept.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Filesystem
{
    public function __construct(string $namespace = null)
    {
        $fs = new FilesystemUtils();

        if (null === $namespace) {
            $reflection = new \ReflectionClass($this);

            $namespace = $reflection->getShortName();
        }

        $this->namespace = $namespace;

        $this->__destruct();
        $fs->mkdir($this->getRootPath());
    }

    public function __destruct()
    {
        $fs = new FilesystemUtils();
        $root = $this->getRootPath();

        if ($fs->exists($root)) {
            $fs->chmod($root, 0700, 0000, true);
            $fs->remove($root);
        }
    }

    /**
     * Get the absolute path to the file located at `$path`.
     */
    public function getFilePath(string $path): string
    {
        if (0 !== \strpos($path, '/')) {
            throw new \InvalidArgumentException('Absolute path must be given.');
        }

        return \sprintf('%s%s',
            static::getRootPath(),
            $path
        );
    }

    /**
     * Create filesystem fixtures following the specified structure.
     *
     * The structure is an associative array where the key is the _filename_
     * and the value is the _specification_.
     *
     * Allowed specifications are:
     *  - a `string`: the file is a regular file with the specified content,
     *  - an **octal** `integer`: the file is an empty regular file with the
     *    specified access permissions,
     *  - `null`: the file is guaranted to be non existent,
     *  - an `array` containing a **single octal** `integer`: the file is an
     *    empty directory with the specified permissions,
     *  - an **associative** `array`: the file is a directory containing the
     *    specified structure.
     */
    public function createFiles(array $structure): void
    {
        self::createFileStructure(static::getRootPath(), $structure);
    }

    /**
     * Get the absolute path to the fixtures root directory.
     */
    private function getRootPath(): string
    {
        return \sprintf('%s/%s/%s',
            \sys_get_temp_dir(),
            \str_replace('\\', '/', __NAMESPACE__),
            $this->namespace
        );
    }

    /**
     * Create the file structure with the given parent directory.
     */
    private function createFileStructure(string $parentPath, array $structure): void
    {
        $fs = new FilesystemUtils();

        foreach ($structure as $name => $specification) {
            $absolutePath = "${parentPath}/${name}";
            $type = \gettype($specification);

            switch ($type) {
                // regular file
                case 'string':
                    $fs->dumpFile($absolutePath, $specification);

                    break;

                // directory
                case 'array':
                    $fs->mkdir($absolutePath);

                    // If the specification is an array containing a single
                    // integer, handle it as a directory with custom permissions.
                    // Otherwize, create the substructure.
                    if (isset($specification[0]) && \is_int($specification[0])) {
                        $fs->chmod($absolutePath, $specification[0]);
                    } else {
                        self::createFileStructure($absolutePath, $specification);
                    }

                    break;

                // regular file with set permissions
                case 'integer':
                    $fs->dumpFile($absolutePath, '');
                    $fs->chmod($absolutePath, $specification);

                    break;

                // not existing file
                case 'NULL':
                    if ($fs->exists($absolutePath)) {
                        $fs->remove($absolutePath);
                    }

                    break;

                // not handled specification
                default:
                    throw new \InvalidArgumentException(\sprintf(
                        'Specification of type "%s" not handled.',
                        $type
                    ));
            }
        }
    }
}
