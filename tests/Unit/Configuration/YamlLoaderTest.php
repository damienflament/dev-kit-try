<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Configuration\YamlLoader;
use Symfony\Cmf\DevKit\Tests\Fixtures\Filesystem;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * The configuration loader tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class YamlLoaderTest extends TestCase
{
    const VALID_CONFIGURATION = <<<'YAML'
organization:
    name: acme

repositories:
    - foo
    - bar

user:
    name: john_doe
    real_name: John Doe
    email: john.doe@acme.org
    token: '%env(GITHUB_TOKEN)%'
YAML;

    private $fixtures;

    public function setUp(): void
    {
        $this->fixtures = new Filesystem();

        $this->fixtures->createFiles([
            'valid.yml' => static::VALID_CONFIGURATION,
            'invalid.yml' => 'unknown_key: value',
            'not_readable.yml' => 0300,
            'not_existing.yml' => null,
            'directory' => [],
        ]);
    }

    /**
     * It should throw an exception when constructed using a not existing file.
     */
    public function testThrowExceptionWithNotExistingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        new YamlLoader($this->fixtures->getFilePath('/not_existing.yml'));
    }

    /**
     * It should throw an exception when construted using a not readable file.
     */
    public function testThrowExceptionWithNotReadableFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not readable');

        new YamlLoader($this->fixtures->getFilePath('/not_readable.yml'));
    }

    /**
     * It should throw an exception when construted using a directory.
     */
    public function testThrowExceptionWithDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a regular file');

        new YamlLoader($this->fixtures->getFilePath('/directory'));
    }

    /**
     * It should throw an {@see InvalidConfigurationException} when loading an
     * invalid configuration file.
     */
    public function testLoadThrowExceptionWithInvalidConfiguration(): void
    {
        $loader = new YamlLoader($this->fixtures->getFilePath('/invalid.yml'));

        $this->expectException(InvalidConfigurationException::class);
        $loader->load('root');
    }

    /**
     * It should load the configuration from a YAML file.
     */
    public function testLoad(): void
    {
        $loader = new YamlLoader($this->fixtures->getFilePath('/valid.yml'));

        \putenv('GITHUB_TOKEN=0123456789');

        $this->assertSame([
            'organization' => [
                'name' => 'acme',
            ],
            'repositories' => ['foo', 'bar'],
            'user' => [
                'name' => 'john_doe',
                'real_name' => 'John Doe',
                'email' => 'john.doe@acme.org',
                'token' => '0123456789',
            ],
        ], $loader->load('config'));
    }
}
