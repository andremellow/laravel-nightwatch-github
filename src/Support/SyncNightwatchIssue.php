<?php

namespace Andremellow\NightwatchGithub\Support;

use Andremellow\NightwatchGithub\Contracts\GitHubIssues;
use Andremellow\NightwatchGithub\Models\NightwatchGithubIssue;
use Illuminate\Support\Facades\Cache;

class SyncNightwatchIssue
{
    public function __construct(private readonly GitHubIssues $github) {}

    /** @param array<string, mixed> $webhook */
    public function handle(array $webhook): void
    {
        $event = (string) $webhook['event'];
        $issue = $webhook['payload']['issue'];
        $nightwatchId = (string) $issue['id'];

        Cache::lock("nightwatch-github:{$nightwatchId}", 30)->block(10, function () use ($event, $issue, $webhook, $nightwatchId): void {
            $mapping = NightwatchGithubIssue::query()->where('nightwatch_issue_id', $nightwatchId)->first();
            $number = $mapping?->github_issue_number ?? $this->github->findByNightwatchId($nightwatchId);

            if ($number === null && in_array($event, ['issue.opened', 'issue.reopened'], true)) {
                $number = $this->github->create(
                    $this->title($issue),
                    IssueBody::make($webhook['payload']),
                    $this->labels(),
                );
            } elseif ($number !== null) {
                $this->applyExistingEvent($number, $event);
            }

            if ($number !== null) {
                NightwatchGithubIssue::query()->updateOrCreate(
                    ['nightwatch_issue_id' => $nightwatchId],
                    [
                        'github_repository' => (string) config('nightwatch-github.github.repository'),
                        'github_issue_number' => $number,
                        'last_event' => $event,
                    ],
                );
            }
        });
    }

    private function applyExistingEvent(int $number, string $event): void
    {
        match ($event) {
            'issue.opened' => $this->github->comment($number, 'Nightwatch reported another occurrence of this production issue.'),
            'issue.reopened' => $this->reopen($number),
            'issue.resolved' => $this->close($number, 'Nightwatch marked this issue as resolved.'),
            'issue.ignored' => $this->ignore($number),
            default => null,
        };
    }

    private function reopen(int $number): void
    {
        $this->github->update($number, 'open', $this->labels());
        $this->github->comment($number, 'Nightwatch reopened this production issue after a new occurrence.');
    }

    private function close(int $number, string $comment): void
    {
        $this->github->update($number, 'closed', $this->labels());
        $this->github->comment($number, $comment);
    }

    private function ignore(int $number): void
    {
        $labels = [...$this->labels(), (string) config('nightwatch-github.github.ignored_label')];
        $this->github->update($number, 'closed', array_values(array_unique($labels)));
        $this->github->comment($number, 'Nightwatch marked this issue as ignored.');
    }

    /** @param array<string, mixed> $issue */
    private function title(array $issue): string
    {
        $title = preg_replace('/\s+/', ' ', trim((string) ($issue['title'] ?? 'Unknown production issue')));

        return '[Production] '.mb_strimwidth((string) $title, 0, 220, '…');
    }

    /** @return array<int, string> */
    private function labels(): array
    {
        return array_values(array_filter(config('nightwatch-github.github.labels', []), 'is_string'));
    }
}
