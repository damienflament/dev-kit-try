<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Tests\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Cmf\DevKit\Console\Application;
use Symfony\Cmf\DevKit\Console\Command\AbstractCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The dummy command.
 *
 * It allows testing the abstract class.
 */
class DummyCommand extends AbstractCommand
{
    public $ouput;

    public function configure(): void
    {
        $this->setName('dummy');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
    }
}

/**
 * The abstract command tests for concrete methods.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class AbstractCommandTest extends TestCase
{
    private $command;

    public function setUp(): void
    {
        $application = $this->prophesize(Application::class);
        $application->getHelperSet()
            ->willReturn($this->prophesize(HelperSet::class)->reveal());
        $application->getDefinition()
            ->willReturn(new InputDefinition());
        $application->getConfiguration()
            ->willReturn([
                'foo' => [
                    'bar' => 'foobar',
                    'baz' => 'foobaz',
                ],
            ]);
        $application->getCacheDirectory()
            ->willReturn('/cache');

        $this->command = new DummyCommand();
        $this->command->setApplication($application->reveal());
    }

    /**
     * It should throw an exception when requesting a non existing group.
     */
    public function testGetConfigurationThrowExceptionWithNotExistingGroup(): void
    {
        $groupName = 'not-existing-group';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'The configuration group "%s" does not exists.',
            $groupName
        ));

        $this->command->getConfiguration($groupName);
    }

    /**
     * It should give the configuration contained in the specified group.
     */
    public function testGetConfiguration(): void
    {
        $this->assertSame([
            'bar' => 'foobar',
            'baz' => 'foobaz',
        ], $this->command->getConfiguration('foo'));
    }

    /**
     * It should give the location of the specified cache directory.
     */
    public function testGetCacheDirectory(): void
    {
        $this->assertSame(
            '/cache/foo',
            $this->command->getCacheDirectory('foo')
        );
    }

    /**
     * It should use the Symfony style.
     */
    public function testRunUsingSymfonyStyle(): void
    {
        $input = $this->prophesize(InputInterface::class)->reveal();
        $output = new NullOutput();

        $this->command->run($input, $output);

        $this->assertInstanceOf(SymfonyStyle::class, $this->command->output);
    }
}
