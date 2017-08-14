<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Console;

use Symfony\Cmf\DevKit\Configuration\LoaderInterface;
use Symfony\Cmf\DevKit\Console\Command\DispatchCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * The Symfony CMF console application for maintainers.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Application extends BaseApplication
{
    private $id;
    private $loader;
    private $cacheDirectory;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        parent::__construct($name);

        $this->loader = null;
        $this->configuration = null;

        $this->add(new DispatchCommand());
    }

    /**
     * Get the identifier of the application.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the configuration loader used by the application.
     */
    public function setConfigurationLoader(LoaderInterface $loader): void
    {
        $this->loader = $loader;
    }

    /**
     * Return the application configuration.
     *
     * If the loader has not been set, throw a `LogicException`.
     */
    public function getConfiguration(): array
    {
        if (null === $this->loader) {
            throw new \LogicException('The configuration loader has not been set.');
        }

        return $this->loader->load();
    }

    /**
     * Set the cache directory location.
     */
    public function setCacheDirectory(string $path): void
    {
        $this->cacheDirectory = $path;
    }

    /**
     * Return the application cache directory location.
     */
    public function getCacheDirectory(): string
    {
        if (null === $this->cacheDirectory) {
            throw new \LogicException('The cache directory location has not been set.');
        }

        return $this->cacheDirectory;
    }
}
