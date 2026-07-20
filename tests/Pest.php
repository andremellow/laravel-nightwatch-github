<?php

use Andremellow\NightwatchGithub\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function nightwatchPayload(string $event = 'issue.opened', string $environment = 'production'): array
{
    return [
        'event' => $event,
        'timestamp' => '2026-07-20T12:00:00.000000Z',
        'payload' => [
            'issue' => [
                'id' => '9d4e2c1a-1234-4678-abcd-ef0123456789',
                'ref' => 42,
                'type' => 'exception',
                'title' => 'RuntimeException: Production failed',
                'status' => 'open',
                'priority' => 'high',
                'url' => 'https://nightwatch.laravel.com/us/applications/app/issues/42',
                'details' => [
                    'class' => 'RuntimeException',
                    'message' => 'Production failed',
                    'file' => 'app/Actions/Example.php',
                    'line' => 42,
                ],
            ],
            'environment' => ['name' => $environment],
        ],
    ];
}
