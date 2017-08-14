<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\DevKit\Github;

use Github\Client;

/**
 * A Github opened pull request.
 *
 * Only one opened pull request can be created on a repository from a specified
 * base branch to a specified head branch.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 *
 * @codeCoverageIgnore Testing a simple wrapper without depency injection seems hard an useless for now.
 */
class PullRequest
{
    private $client;
    private $organisation;
    private $repository;
    private $baseBranch;
    private $headBranch;

    public function __construct(string $organisation, string $repository, string $baseBranch, string $headBranch)
    {
        $this->client = new Client();

        $this->organisation = $organisation;
        $this->repository = $repository;
        $this->baseBranch = $baseBranch;
        $this->headBranch = $headBranch;
    }

    /**
     * Authenticate to the repository.
     */
    public function authenticate(string $token): void
    {
        $this->client->authenticate($token, Client::AUTH_URL_TOKEN);
    }

    /**
     * Check if an open pull request from the same head branch to the same base
     * branch exists.
     */
    public function exists(): bool
    {
        return null !== $this->getData();
    }

    /**
     * Create the pull request.
     *
     * Requires authentication. See {@see PullRequest::authenticate()}.
     */
    public function create(string $title, string $body): void
    {
        $this->client->api('pull_request')
            ->create($this->organisation, $this->repository, [
                'base' => $this->baseBranch,
                'head' => $this->headBranch,
                'title' => $title,
                'body' => $body,
            ]);
    }

    /**
     * Update the pull request.
     *
     * Requires authentication. See {@see PullRequest::authenticate()}.
     */
    public function update(string $title, string $body): void
    {
        $data = $this->getData();

        $this->client->api('pull_request')
            ->update($this->organisation, $this->repository, $data['number'], [
                'title' => $title,
                'body' => $body,
            ]);
    }

    /**
     * Get the data related to this pull request.
     *
     * If this pull request has not been created, null is returned.
     */
    private function getData(): ?array
    {
        $pullRequests = $this->client->api('pull_request')
            ->all($this->organisation, $this->repository, [
                'state' => 'open',
                'base' => $this->baseBranch,
                'head' => $this->headBranch,
            ]);

        if (0 === \count($pullRequests)) {
            return null;
        }

        return $pullRequests[0];
    }
}
