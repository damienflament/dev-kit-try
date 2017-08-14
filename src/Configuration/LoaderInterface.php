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

/**
 * The interface implemented by the configuration loader.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
interface LoaderInterface
{
    /**
     * Load and return the configuration.
     */
    public function load(): array;
}
