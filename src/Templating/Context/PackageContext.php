<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Templating\Context;

use Composer\Factory;
use Composer\IO\NullIO;

/**
 * The Composer root package context make available data from a `composer.json` file.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class PackageContext implements ContextInterface
{
    private $package;

    public function __construct(string $path)
    {
        $factory = new Factory();
        $composer = $factory->createComposer(
            new NullIO(),
            $path,
            false,
            \dirname($path),
            true
        );

        $this->package = $composer->getPackage();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'package';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'name' => $this->package->getName(),
            'shortName' => $this->getShortName(),
            'prettyName' => $this->getPrettyName(),

            'description' => $this->package->getDescription(),
            'authors' => $this->package->getAuthors(),
            'homepage' => $this->package->getHomepage(),
            'type' => $this->package->getType(),
            'licenses' => $this->package->getLicense(),

            'stability' => $this->package->getStability(),

            'dependencies' => [
                'production' => $this->getDependencies(),
                'development' => $this->getDevelopmentDependencies(),
            ],
        ];
    }

    /**
     * Return the short name of the package (without the vendor name).
     *
     * `baz/foo-bar` becomes `foo-bar`.
     */
    private function getShortName(): string
    {
        $name = $this->package->getName();
        $components = \explode('/', $name);

        return \end($components);
    }

    /**
     * Return the pretty name of the package computed from its short name.
     *
     * `foo-bar` becomes `Foo Bar`.
     */
    private function getPrettyName(): string
    {
        $shortName = $this->getShortName($this->package);
        $upperName = \ucwords($shortName, '-');
        $prettyName = \str_replace('-', "\x20", $upperName);

        return $prettyName;
    }

    /**
     * Return the list of dependencies for production.
     */
    private function getDependencies(): array
    {
        return \array_keys($this->package->getRequires());
    }

    /**
     * Return the list of dependencies for development.
     */
    private function getDevelopmentDependencies(): array
    {
        return \array_keys($this->package->getDevRequires());
    }
}
