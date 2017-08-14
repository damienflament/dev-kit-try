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

use Symfony\Cmf\DevKit\Console\Application;

/**
 * The application context make available data from the Symfony CMF Development
 * Kit application.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class ApplicationContext implements ContextInterface
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'application';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'id' => $this->application->getId(),
            'name' => $this->application->getName(),
        ];
    }
}
