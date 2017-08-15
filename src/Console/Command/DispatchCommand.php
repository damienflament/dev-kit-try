<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Console\Command;

use Symfony\Cmf\DevKit\Git\WorkingCopy;
use Symfony\Cmf\DevKit\Github\PullRequest;
use Symfony\Cmf\DevKit\Templating\Context\ApplicationContext;
use Symfony\Cmf\DevKit\Templating\Context\PackageContext;
use Symfony\Cmf\DevKit\Templating\Context\TravisContext;
use Symfony\Cmf\DevKit\Templating\Loader;
use Symfony\Cmf\DevKit\Templating\Renderer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command dispatching the shared files.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class DispatchCommand extends AbstractCommand
{
    private $renderer;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('dispatch')
            ->setDescription('Dispatches the shared files to the repositories')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show the modifications which would be applied to the files');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $loader = new Loader(__DIR__.'/../../../resources/templates');
        $loader->addPath('shared_files', 'shared');
        $loader->addPath('dev-kit', 'dev-kit');

        $this->renderer = new Renderer($loader);
        $this->renderer->setCacheDirectory($this->getCacheDirectory('rendering'));
        $this->renderer->addContext(new ApplicationContext($this->getApplication()));

        if (TravisContext::onBuildMachine()) {
            $this->renderer->addContext(new TravisContext());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');

        $organization = $this->getConfiguration('organization')['name'];
        $user = $this->getConfiguration('user');
        $repositories = $this->getConfiguration('repositories');
        $branchName = $this->getApplication()->getId();

        foreach ($repositories as $repository) {
            $directory = $this->getWorkingCopyDirectory($repository);
            $url = $this->getRepositoryUrl($repository);

            $output->title(\sprintf(
                'Dispatching the shared files on the <info>%s</info> repository',
                $repository
            ));

            $output->text('Pulling the repository...');
            $workingCopy = WorkingCopy::createFromRemote($directory, $url);

            $output->text(\sprintf(
                'Preparing the <info>%s</info> branch...',
                $branchName
            ));
            $workingCopy->checkoutRemoteBranch($branchName);

            $output->text('Generating the files...');
            $this->renderer->addContext(new PackageContext("${directory}/composer.json"));

            $this->renderer->renderNamespace('shared', $directory);

            if (!$workingCopy->hasChanges()) {
                $output->text('<comment>No changes are needed.</comment>');

                continue;
            }

            if ($isDryRun || $output->isVerbose()) {
                $this->showChangedFilesStatus($workingCopy, $output);
            }

            if ($isDryRun || $output->isVeryVerbose()) {
                $this->showChangedFilesDiffs($workingCopy, $output);
            }

            if ($isDryRun) {
                $output->text('Resetting the changes...');
                $workingCopy->reset();

                continue;
            }

            $output->text('Pushing the modified files...');
            $workingCopy->setAuthor($user['real_name'], $user['email']);
            $workingCopy->commit($this->renderer->renderBlock('@dev-kit/pull_request.md', 'title'));
            $workingCopy->push();

            $pullRequest = new PullRequest($organization, $repository, 'master', $branchName);
            $pullRequest->authenticate($user['token']);
            $pullRequestTitle = $this->renderer->renderBlock('@dev-kit/pull_request.md', 'title');
            $pullRequestBody = $this->renderer->renderBlock('@dev-kit/pull_request.md', 'body');

            if ($pullRequest->exists()) {
                $output->text('Updating the existing pull request...');

                $pullRequest->update($pullRequestTitle, $pullRequestBody);
            } else {
                $output->text('Creating a pull request...');

                $pullRequest->create($pullRequestTitle, $pullRequestBody);
            }

            $output->text("<info>Files dispatched to the <comment>${repository}</comment> repository.</info>");
        }

        return 0;
    }

    private function getWorkingCopyDirectory(string $repository): string
    {
        $organization = $this->getConfiguration('organization')['name'];

        return \sprintf('%s/%s/%s',
            $this->getCacheDirectory('git'),
            $organization,
            $repository
        );
    }

    private function getRepositoryUrl(string $repository): string
    {
        $organization = $this->getConfiguration('organization')['name'];
        $user = $this->getConfiguration('user');

        return \sprintf('https://%s:%s@github.com/%s/%s',
            $user['name'],
            $user['token'],
            $organization,
            $repository
        );
    }

    private function showChangedFilesStatus(WorkingCopy $workingCopy, OutputInterface $output): void
    {
        $changes = $workingCopy->getChanges();

        $output->newline();
        $output->table(
            ['Filename', 'Status'],
            $this->getRowsFromAssociativeArray($changes)
        );
    }

    private function showChangedFilesDiffs(WorkingCopy $workingCopy, OutputInterface $output): void
    {
        $changedFiles = $workingCopy->getChangedFiles();

        foreach ($changedFiles as $filename) {
            $output->section($filename);
            $output->block($workingCopy->getDiffByFile($filename));
            $output->newline();
        }
    }

    private function getRowsFromAssociativeArray(array $array): array
    {
        $rows = [];

        foreach ($array as $key => $value) {
            $rows[] = [$key, $value];
        }

        return $rows;
    }
}
