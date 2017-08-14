<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * A Loader reading the configuration from a YAML file.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class YamlLoader implements LoaderInterface
{
    private $path;

    /**
     * Construct the loader for the Yaml file located at the given `path`.
     */
    public function __construct(string $path)
    {
        if (!\file_exists($path)) {
            throw new \InvalidArgumentException(\sprintf(
                'The file "%s" does not exist.',
                $path
            ));
        }

        if (!\is_file($path)) {
            throw new \InvalidArgumentException(\sprintf(
                'The file "%s" is not a regular file.',
                $path
            ));
        }

        if (!\is_readable($path)) {
            throw new \InvalidArgumentException(\sprintf(
                'The file "%s" is not readable.',
                $path
            ));
        }

        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function load(): array
    {
        $processor = new Processor();
        $definition = new Definition();
        $content = Yaml::parse(\file_get_contents($this->path));

        return $processor->processConfiguration(
            $definition,
            ['config' => $content]
        );
    }
}
