<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Templating;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * The templates loader.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Loader extends FilesystemLoader
{
    private $rootPath;

    public function __construct(string $rootPath)
    {
        parent::__construct([], $rootPath);

        $this->rootPath = $rootPath;
    }

    /**
     * Return the logical names of the templates located under the specified
     * namespace.
     */
    public function getNamespaceTemplateNames(string $namespace): array
    {
        $finder = new Finder();
        $paths = $this->getPaths($namespace);

        if (empty($paths)) {
            throw new LoaderError(\sprintf(
                'There are no registered paths for namespace "%s".',
                $namespace
            ));
        }

        $absolutePaths = \array_map(function (string $path): string {
            return \realpath(\sprintf('%s/%s', $this->rootPath, $path));
        }, $paths);

        $finder->files()->in($absolutePaths)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->sortByName();

        $logicalNames = \array_map(function (SplFileInfo $file): string {
            return $file->getRelativePathname();
        }, \iterator_to_array($finder));

        return \array_values($logicalNames);
    }
}
