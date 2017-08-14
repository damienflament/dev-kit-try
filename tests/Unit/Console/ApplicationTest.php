<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Configuration\LoaderInterface;
use Symfony\Cmf\DevKit\Console\Application;

/**
 * The application tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class ApplicationTest extends TestCase
{
    private $application;

    public function setUp(): void
    {
        $this->application = new Application('test-application', 'The Testing Application');
    }

    /**
     * It should give its identifier.
     */
    public function testGetId(): void
    {
        $this->assertSame('test-application', $this->application->getId());
    }

    /**
     * It should throw an exception when getting the configuration before the
     * loader has been set.
     */
    public function testGetConfigurationThrowExceptionWithoutLoader(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The configuration loader has not been set');

        $this->application->getConfiguration();
    }

    public function provideGetConfiguration(): array
    {
        $configurations = [
            'empty configuration' => [],
            'not empty configuration' => ['foo' => 'bar'],
        ];

        $datasets = \array_map(function ($configuration) {
            $loader = $this->prophesize(LoaderInterface::class);

            $loader->load()->willReturn($configuration);

            return [
                'loader' => $loader->reveal(),
                'expected' => $configuration,
            ];
        }, $configurations);

        return $datasets;
    }

    /**
     * It should give its configuration.
     *
     * @dataProvider provideGetConfiguration
     */
    public function testGetConfiguration(LoaderInterface $loader, array $expected): void
    {
        $this->application->setConfigurationLoader($loader);

        $configuration = $this->application->getConfiguration();

        $this->assertSame($expected, $configuration);
    }

    /**
     * It should throw an exception when getting the cache directory before tit
     * has been set.
     */
    public function testGetCacheDirectoryThrowExceptionWhenUnset(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The cache directory location has not been set.');

        $this->application->getCacheDirectory();
    }

    /**
     * It should set its cache directory.
     */
    public function testSetCacheDirectory(): void
    {
        $path = '/cache';

        $this->application->setCacheDirectory($path);

        $this->assertSame($path, $this->application->getCacheDirectory());
    }
}
