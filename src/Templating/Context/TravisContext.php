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

/**
 * The Travis CI context make available data from build machine environment.
 *
 * @author Damien Flament <damien.flament@gmx.com>
 */
class TravisContext implements ContextInterface
{
    public static function onBuildMachine(): bool
    {
        return 'true' === \getenv('TRAVIS');
    }

    public function __construct()
    {
        if (!static::onBuildMachine()) {
            throw new \LogicException('Travis environment variables not found');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'travis';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        [$title, $body] = $this->getCommitMessage();

        return [
            'repository' => \getenv('TRAVIS_REPO_SLUG'),
            'commit' => [
                'title' => $title,
                'body' => $body,
            ],
        ];
    }

    /**
     * Return the commit message as an `array` where the first element is the
     * _title_ and the second the _body_.
     *
     * The title is the part of the message before the first blank line.
     */
    private function getCommitMessage(): array
    {
        $message = \getenv('TRAVIS_COMMIT_MESSAGE');
        $lines = \explode(\PHP_EOL, $message);

        // Get the lines before the first blank line as the title
        $titleLines = [];

        do {
            $line = \array_shift($lines);

            if (empty(\trim($line))) {
                break;
            }

            $titleLines[] = $line;
        } while (!empty($lines));

        // Set the remaining lines as the body
        $bodyLines = $lines;

        // Build the title and body from the extracted lines
        $title = \implode(\PHP_EOL, $titleLines);
        $body = \implode(\PHP_EOL, $bodyLines);

        return [$title, $body];
    }
}
