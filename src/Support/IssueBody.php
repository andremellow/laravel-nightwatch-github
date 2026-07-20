<?php

namespace Andremellow\NightwatchGithub\Support;

class IssueBody
{
    /** @param array<string, mixed> $payload */
    public static function make(array $payload): string
    {
        $issue = $payload['issue'];
        $environment = $payload['environment']['name'] ?? 'unknown';
        $details = is_array($issue['details'] ?? null) ? $issue['details'] : [];

        $rows = [
            ['Nightwatch issue', '#'.($issue['ref'] ?? 'unknown')],
            ['Environment', (string) $environment],
            ['Type', (string) ($issue['type'] ?? 'unknown')],
            ['Priority', (string) ($issue['priority'] ?? 'none')],
            ['Exception', (string) ($details['class'] ?? 'n/a')],
            ['Location', self::location($details)],
        ];

        $table = collect($rows)
            ->map(fn (array $row): string => '| '.self::escape($row[0]).' | '.self::escape($row[1]).' |')
            ->implode("\n");

        $message = self::codeBlock((string) ($details['message'] ?? $issue['title'] ?? 'No message provided.'));
        $url = (string) ($issue['url'] ?? '');
        $id = (string) $issue['id'];

        return <<<MARKDOWN
            ## Production issue detected by Laravel Nightwatch

            | Field | Value |
            | --- | --- |
            {$table}

            ### Error message

            {$message}

            [Open this issue in Nightwatch]({$url})

            <!-- nightwatch-issue-id:{$id} -->
            MARKDOWN;
    }

    /** @param array<string, mixed> $details */
    private static function location(array $details): string
    {
        $file = $details['file'] ?? null;
        $line = $details['line'] ?? null;

        return is_string($file) ? $file.($line !== null ? ':'.$line : '') : 'n/a';
    }

    private static function escape(string $value): string
    {
        return str_replace(['|', "\r", "\n"], ['\\|', ' ', ' '], $value);
    }

    private static function codeBlock(string $value): string
    {
        return "```text\n".str_replace('```', "`\u{200B}``", $value)."\n```";
    }
}
