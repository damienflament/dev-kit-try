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

use Symfony\Cmf\DevKit\Templating\Context\ContextInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * The templates renderer.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Renderer
{
    protected $loader;
    protected $contexts;
    protected $twig;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
        $this->contexts = [];
        $this->twig = new Environment($loader, [
            'auto_reload' => true,
            'autoescape' => 'name',
        ]);
    }

    /**
     * Set the cache directory location.
     */
    public function setCacheDirectory(string $directory): void
    {
        $this->twig->setCache($directory);
    }

    /**
     * Return the directory where rendering cache files are stored.
     */
    public function getCacheDirectory(): string
    {
        $cacheDirectory = $this->twig->getCache();

        if (false === $cacheDirectory) {
            throw new \LogicException('The cache directory location has not been set.');
        }

        return $cacheDirectory;
    }

    /**
     * Add or replace a context to those available.
     */
    public function addContext(ContextInterface $context): void
    {
        $this->contexts[$context->getName()] = $context;
    }

    /**
     * Return the parameters available to the templates.
     */
    public function getParameters(): array
    {
        $parameters = [];

        foreach ($this->contexts as $context) {
            $parameters[$context->getName()] = $context->getParameters();
        }

        return $parameters;
    }

    /**
     * Render the specified template.
     */
    public function render(string $name): string
    {
        return $this->twig->render($name, $this->getParameters());
    }

    /**
     * Render the specified block from the specified template.
     */
    public function renderBlock(string $templateName, string $blockName): string
    {
        $template = $this->twig->load($templateName);

        return $template->renderBlock($blockName, $this->getParameters());
    }

    /**
     * Render the templates located under the specified namespace into the
     * specified directory.
     */
    public function renderNamespace(string $namespace, string $directory): void
    {
        $fs = new Filesystem();
        $logicalNames = $this->loader->getNamespaceTemplateNames($namespace);

        foreach ($logicalNames as $name) {
            $path = \sprintf('%s/%s', $directory, $name);

            $content = $this->render("@${namespace}/${name}");

            $fs->dumpFile($path, $content);
        }
    }
}
