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

/**
 * The interface of templating contexts.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
interface ContextInterface
{
    /**
     * Get the name of this context.
     *
     * This is also the namespace under which the parameters will be available.
     */
    public function getName(): string;

    /**
     * Get the parameters exposed by the context as an associative array.
     */
    public function getParameters(): array;
}
