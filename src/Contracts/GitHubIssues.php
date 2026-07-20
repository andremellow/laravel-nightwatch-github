<?php

namespace Andremellow\NightwatchGithub\Contracts;

interface GitHubIssues
{
    /** @param array<int, string> $labels */
    public function create(string $title, string $body, array $labels): int;

    /** @param array<int, string> $labels */
    public function update(int $number, string $state, array $labels): void;

    public function comment(int $number, string $body): void;

    public function findByNightwatchId(string $nightwatchIssueId): ?int;
}
