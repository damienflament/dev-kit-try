<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Templating;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Templating\Context\ContextInterface;
use Symfony\Cmf\DevKit\Templating\Loader;
use Symfony\Cmf\DevKit\Templating\Renderer;
use Symfony\Cmf\DevKit\Tests\Fixtures\Filesystem;

/**
 * The templates renderer tests.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class RendererTest extends TestCase
{
    private $renderer;
    private $fixtures;

    public function setUp(): void
    {
        $this->fixtures = new Filesystem();

        $this->fixtures->createFiles([
            'templates' => [
                'namespace' => [
                    'empty' => '',
                    'template' => 'lorem ipsum',
                    'template_with_blocks' => '{%block foo%}consectetur adipiscing elit{%endblock%} {%block bar%}sed do eiusmod{%endblock%}',
                    'empty_directory' => [],
                    'directory' => [
                        'empty' => '',
                        'template' => 'dolor sit amet',
                        'template_with_blocks' => '{%block bar%}tempor incididunt ut labore{%endblock%} {%block baz%}et dolore magna aliqua{%endblock%}',
                    ],
                ],
            ],
            'rendering' => [],
        ]);

        $loader = new Loader($this->fixtures->getFilePath('/templates'));
        $loader->addPath('namespace');

        $this->renderer = new Renderer($loader);
    }

    /**
     * It should throw an exception when getting the cache directory before it
     * has been set.
     */
    public function testGetCacheDirectoryThrowExceptionWhenUnset(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The cache directory location has not been set.');

        $this->renderer->getCacheDirectory();
    }

    /**
     * It should set the cache directory location and use it.
     */
    public function testSetCacheDirectory(): void
    {
        $cacheDirectory = '/cache';

        $this->renderer->setCacheDirectory($cacheDirectory);

        $this->assertSame($cacheDirectory, $this->renderer->getCacheDirectory());
    }

    /**
     * It should add the given context.
     */
    public function testAddContext(): Renderer
    {
        $context = $this->prophesize(ContextInterface::class);
        $context->getName()->willReturn('foo');
        $context->getParameters()->willReturn(['bar' => 'baz']);

        $this->assertEmpty($this->renderer->getParameters());

        $this->renderer->addContext($context->reveal());
        $this->assertSame(
            ['foo' => ['bar' => 'baz']],
            $this->renderer->getParameters()
        );

        return $this->renderer;
    }

    /**
     * It should replace an existing context with a new one.
     *
     * @depends testAddContext
     */
    public function testAddContextReplaceExisting(Renderer $renderer): void
    {
        $context = $this->prophesize(ContextInterface::class);
        $context->getName()->willReturn('foo');
        $context->getParameters()->willReturn(['bar' => 'other baz']);

        $renderer->addContext($context->reveal());
        $this->assertSame(
            ['foo' => ['bar' => 'other baz']],
            $renderer->getParameters()
        );
    }

    // public function provideRender()
    // {
    //     return [
    //         'an empty template' => [
    //             'empty',
    //             '',
    //         ],
    //         'an empty template in a directory' => [
    //             'directory/empty',
    //             '',
    //         ],
    //         'a template' => [
    //             'template',
    //             'lorem ipsum',
    //         ],
    //         'a template in a directory' => [
    //             'directory/template',
    //             'dolor sit amet',
    //         ],
    //     ];
    // }

    // /**
    //  * It should render the template.
    //  *
    //  * @dataProvider provideRender
    //  */
    // public function testRenderBlock(string $templateFilename, string $expectedContent)
    // {
    //     $this->assertSame(
    //         $expectedContent,
    //         $this->renderer->render($templateFilename)
    //     );
    // }

    public function provideRender(): array
    {
        return [
            'an empty template' => [
                'empty',
                '',
            ],
            'an empty template in a directory' => [
                'directory/empty',
                '',
            ],
            'a template' => [
                'template',
                'lorem ipsum',
            ],
            'a template in a directory' => [
                'directory/template',
                'dolor sit amet',
            ],
            'a template with blocks' => [
                'template_with_blocks',
                'consectetur adipiscing elit sed do eiusmod',
            ],
            'a template with blocks in a directory' => [
                'directory/template_with_blocks',
                'tempor incididunt ut labore et dolore magna aliqua',
            ],
        ];
    }

    /**
     * It should render the template.
     *
     * @dataProvider provideRender
     */
    public function testRender(string $templateFilename, string $expectedContent): void
    {
        $this->assertSame(
            $expectedContent,
            $this->renderer->render($templateFilename)
        );
    }

    /**
     * It should render the templates into the specified directory.
     *
     * @dataProvider provideRender
     */
    public function testRenderNamespace(string $templateFilename, string $expectedContent): void
    {
        $renderedFileUrl = $this->fixtures->getFilePath("/rendering/${templateFilename}");

        $this->renderer->renderNamespace(
            Loader::MAIN_NAMESPACE,
            $this->fixtures->getFilePath('/rendering')
        );

        $this->assertFileExists($renderedFileUrl);
        $this->assertStringEqualsFile($renderedFileUrl, $expectedContent);
    }

    public function provideRenderBlock(): array
    {
        return [
            'a block from a template' => [
                'template_with_blocks',
                'bar',
                'sed do eiusmod',
            ],
            'a block from a template in a directory' => [
                'directory/template_with_blocks',
                'bar',
                'tempor incididunt ut labore',
            ],
        ];
    }

    /**
     * It should render a block from the template.
     *
     * @dataProvider provideRenderBlock
     */
    public function testRenderBlock(string $templateFilename, string $blockName, string $expectedContent): void
    {
        $this->assertSame(
            $expectedContent,
            $this->renderer->renderBlock($templateFilename, $blockName)
        );
    }
}
