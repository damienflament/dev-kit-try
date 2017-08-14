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

use Symfony\Cmf\DevKit\Console\Application;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The application configuration definition.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class Definition implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $config = $builder->root('config');

        $config
            ->children()
                ->arrayNode('organization')
                    ->info('the GitHub organization')
                    ->isRequired()
                    ->children()
                        ->scalarNode('name')
                            ->info('the name.')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('user')
                    ->info('the GitHub user account')
                    ->isRequired()
                    ->children()
                        ->scalarNode('name')
                            ->info('the username')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('real_name')
                            ->info('the real name')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('email')
                            ->info('the email address')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('token')
                            ->info('the OAuth token (can be specified using an environment variable)')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifString()
                                ->then(function (string $value): string {
                                    return $this->parseEnv($value);
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('repositories')
                    ->info('the list of enabled repositories')
                    ->prototype('scalar')->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
            ->end();

        return $builder;
    }

    /**
     * Apply the `env()` function if it is used in the given string value.
     *
     * If the specified environment variable is not found, throw an exception.
     */
    private function parseEnv(string $value): string
    {
        $matches = [];

        if (0 === \preg_match('/^%env\(([A-Z_]+)\)%$/', $value, $matches)) {
            return $value;
        }

        $name = $matches[1];

        $value = \getenv($name);

        if (false === $value) {
            throw new \InvalidArgumentException(sprintf('The environment variable "%s" was not found', $name));
        }

        return $value;
    }
}
