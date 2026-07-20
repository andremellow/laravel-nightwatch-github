<?php

namespace Andremellow\NightwatchGithub;

use Andremellow\NightwatchGithub\Contracts\GitHubIssues;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubClient implements GitHubIssues
{
    public function create(string $title, string $body, array $labels): int
    {
        $this->ensureLabels($labels);

        $response = $this->request()->post($this->repositoryPath('/issues'), [
            'title' => $title,
            'body' => $body,
            'labels' => $labels,
        ])->throw();

        $number = $response->json('number');

        if (! is_int($number)) {
            throw new RuntimeException('GitHub did not return an issue number.');
        }

        return $number;
    }

    public function update(int $number, string $state, array $labels): void
    {
        $this->ensureLabels($labels);

        $this->request()->patch($this->repositoryPath("/issues/{$number}"), [
            'state' => $state,
            'labels' => $labels,
        ])->throw();
    }

    public function comment(int $number, string $body): void
    {
        $this->request()->post($this->repositoryPath("/issues/{$number}/comments"), [
            'body' => $body,
        ])->throw();
    }

    public function findByNightwatchId(string $nightwatchIssueId): ?int
    {
        $repository = $this->repository();
        $marker = "nightwatch-issue-id:{$nightwatchIssueId}";
        $response = $this->request()->get('/search/issues', [
            'q' => "repo:{$repository} is:issue in:body \"{$marker}\"",
            'per_page' => 1,
        ])->throw();

        $number = $response->json('items.0.number');

        return is_int($number) ? $number : null;
    }

    private function request(bool $retry = true): PendingRequest
    {
        $token = config('nightwatch-github.github.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('GITHUB_ISSUES_TOKEN is not configured.');
        }

        $request = Http::baseUrl(rtrim((string) config('nightwatch-github.github.api_url'), '/'))
            ->withToken($token)
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'laravel-nightwatch-github',
            ])
            ->timeout(15);

        return $retry ? $request->retry(3, 250, throw: false) : $request;
    }

    private function repositoryPath(string $suffix): string
    {
        return '/repos/'.$this->repository().$suffix;
    }

    private function repository(): string
    {
        $repository = config('nightwatch-github.github.repository');

        if (! is_string($repository) || ! preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository)) {
            throw new RuntimeException('GITHUB_REPOSITORY must use the owner/repository format.');
        }

        return $repository;
    }

    /** @param array<int, string> $labels */
    private function ensureLabels(array $labels): void
    {
        foreach (array_unique($labels) as $label) {
            $response = $this->request(retry: false)->get($this->repositoryPath('/labels/'.rawurlencode($label)));

            if ($response->status() === 404) {
                $this->request()->post($this->repositoryPath('/labels'), [
                    'name' => $label,
                    'color' => $label === 'production-bug' ? 'b60205' : 'd73a4a',
                    'description' => $label === 'production-bug'
                        ? 'A bug detected in the production environment.'
                        : 'Managed by Laravel Nightwatch GitHub integration.',
                ])->throw();

                continue;
            }

            $response->throw();
        }
    }
}
