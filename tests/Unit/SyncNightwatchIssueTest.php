<?php

use Andremellow\NightwatchGithub\Contracts\GitHubIssues;
use Andremellow\NightwatchGithub\Models\NightwatchGithubIssue;
use Andremellow\NightwatchGithub\Support\SyncNightwatchIssue;

it('creates and maps a new production issue with the required labels', function () {
    $github = Mockery::mock(GitHubIssues::class);
    $github->shouldReceive('findByNightwatchId')->once()->andReturnNull();
    $github->shouldReceive('create')->once()
        ->withArgs(fn (string $title, string $body, array $labels) => str_starts_with($title, '[Production]')
            && str_contains($body, 'nightwatch-issue-id:9d4e2c1a-1234-4678-abcd-ef0123456789')
            && $labels === ['bug', 'production-bug', 'nightwatch'])
        ->andReturn(73);

    app()->instance(GitHubIssues::class, $github);

    app(SyncNightwatchIssue::class)->handle(nightwatchPayload());

    expect(NightwatchGithubIssue::query()->first())
        ->github_issue_number->toBe(73)
        ->last_event->toBe('issue.opened');
});

it('reopens the mapped github issue when nightwatch reopens it', function () {
    NightwatchGithubIssue::query()->create([
        'nightwatch_issue_id' => '9d4e2c1a-1234-4678-abcd-ef0123456789',
        'github_repository' => 'owner/repository',
        'github_issue_number' => 73,
        'last_event' => 'issue.resolved',
    ]);

    $github = Mockery::mock(GitHubIssues::class);
    $github->shouldReceive('update')->once()->with(73, 'open', ['bug', 'production-bug', 'nightwatch']);
    $github->shouldReceive('comment')->once()->with(73, Mockery::type('string'));

    app()->instance(GitHubIssues::class, $github);

    app(SyncNightwatchIssue::class)->handle(nightwatchPayload('issue.reopened'));

    expect(NightwatchGithubIssue::query()->first()->last_event)->toBe('issue.reopened');
});
