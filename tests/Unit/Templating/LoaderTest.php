<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Templating\Loader;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Templating\Loader;
use Symfony\Cmf\DevKit\Tests\Fixtures\Filesystem;
use Twig\Error\LoaderError;

/**
 * The directory templates loader tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class LoaderTest extends TestCase
{
    private $fixtures;
    private $loader;

    public function setUp(): void
    {
        $this->fixtures = new Filesystem();

        $this->fixtures->createFiles([
            'templates' => [
                'empty_namespace' => [],
                'foo' => [
                    'bar' => '',
                    'directory' => [
                        'baz' => '',
                    ],
                ],
            ],
        ]);

        $this->loader = new Loader($this->fixtures->getFilePath('/templates'));
        $this->loader->addPath('empty_namespace', 'empty');
        $this->loader->addPath('foo', 'foo');
    }

    public function testGetNotExistingNamespaceTemplateNamesThrowException(): void
    {
        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('There are no registered paths for namespace');

        $this->loader->getNamespaceTemplateNames('not_existing');
    }

    public function provideGetNamespaceTemplateNames(): array
    {
        return [
            'an empty namespace' => [
                'empty',
                [],
            ],
            'a not empty namespace' => [
                'foo',
                ['bar', 'directory/baz'],
            ],
        ];
    }

    /**
     * It should give the logical names of the templates located under a
     * specified namespace.
     *
     * @dataProvider provideGetNamespaceTemplateNames
     */
    public function testGetNamespaceTemplateNames(string $namespace, array $expectedLogicalNames): void
    {
        $this->assertSame(
            $expectedLogicalNames,
            $this->loader->getNamespaceTemplateNames($namespace)
        );
    }
}
