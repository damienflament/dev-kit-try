<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Templating\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Console\Application;
use Symfony\Cmf\DevKit\Templating\Context\ApplicationContext;

/**
 * The application context tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class ApplicationContextTest extends TestCase
{
    private $context;

    public function setUp(): void
    {
        $application = $this->prophesize(Application::class);

        $application->getId()->willReturn('foo');
        $application->getName()->willReturn('Foo');

        $this->context = new ApplicationContext($application->reveal());
    }

    /**
     * It should be named 'application'.
     */
    public function testGetName(): void
    {
        $this->assertSame('application', $this->context->getName());
    }

    /**
     * It should give parameters related to the application.
     */
    public function testGetParameters(): void
    {
        $this->assertSame([
            'id' => 'foo',
            'name' => 'Foo',
        ], $this->context->getParameters());
    }
}
