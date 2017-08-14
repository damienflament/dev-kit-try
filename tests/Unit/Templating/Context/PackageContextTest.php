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
use Symfony\Cmf\DevKit\Templating\Context\PackageContext;
use Symfony\Cmf\DevKit\Tests\Fixtures\Filesystem;

/**
 * The Composer root package context tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class PackageContextTest extends TestCase
{
    const COMPOSER_FILE = <<<'_JSON_'
{
    "name": "baz/foo-bar",
    "homepage": "http://foo.baz.com",
    "description" : "Foo bar baz",
    "license": "MIT",
    "authors" : [
        {
            "name": "Baz",
            "homepage": "http://baz.com",
            "email": "foo@baz.com",
            "role": "developer"
        }
    ],
    "require": {
        "foo/bar": "^1.0",
        "foo/baz": "^1.2"
    },
    "require-dev": {
        "bar/baz": "^2.3"
    }
}
_JSON_;

    private $context;
    private $fixtures;

    public function setUp(): void
    {
        $this->fixtures = new Filesystem();

        $this->fixtures->createFiles([
            'valid.json' => static::COMPOSER_FILE,
            'not_readable.json' => 0300,
            'not_existing.json' => null,
            'directory' => [],
        ]);

        $this->context = new PackageContext($this->fixtures->getFilePath('/valid.json'));
    }

    /**
     * It should be named 'package'.
     */
    public function testGetName(): void
    {
        $this->assertSame('package', $this->context->getName());
    }

    /**
     * It should give parameters related to the Composer root package.
     */
    public function testGetParameters(): void
    {
        $this->assertSame([
            'name' => 'baz/foo-bar',
            'shortName' => 'foo-bar',
            'prettyName' => 'Foo Bar',
            'description' => 'Foo bar baz',
            'authors' => [
                [
                    'name' => 'Baz',
                    'homepage' => 'http://baz.com',
                    'email' => 'foo@baz.com',
                    'role' => 'developer',
                ],
            ],
            'homepage' => 'http://foo.baz.com',
            'type' => 'library',
            'licenses' => ['MIT'],
            'stability' => 'stable',
            'dependencies' => [
                'production' => [
                    'foo/bar',
                    'foo/baz',
                ],
                'development' => [
                    'bar/baz',
                ],
            ],
        ], $this->context->getParameters());
    }
}
