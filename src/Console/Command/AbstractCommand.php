<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The base class for commands.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * Return the configuration contained in the `$name` group.
     *
     * @throws InvalidArgumentException if the specified group does not exists
     */
    public function getConfiguration(string $name): array
    {
        $configuration = $this->getApplication()->getConfiguration();

        if (!isset($configuration[$name])) {
            throw new \InvalidArgumentException(\sprintf(
                'The configuration group "%s" does not exists.',
                $name
            ));
        }

        return $configuration[$name];
    }

    /**
     * Return the location of the specified cache.
     */
    public function getCacheDirectory(string $name): string
    {
        return $this->getApplication()->getCacheDirectory()."/${name}";
    }

    /**
     * {@inheritdoc}
     *
     * Use a `SymfonyStyle` output.
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        return parent::run($input, $output);
    }
}
