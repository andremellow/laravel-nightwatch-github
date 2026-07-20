<?php

return [
    'enabled' => env('NIGHTWATCH_GITHUB_ENABLED', true),

    'route' => [
        'uri' => env('NIGHTWATCH_GITHUB_WEBHOOK_PATH', 'api/webhooks/nightwatch'),
        'name' => 'nightwatch-github.webhook',
        'middleware' => ['api', 'throttle:60,1'],
    ],

    'nightwatch' => [
        'webhook_secret' => env('NIGHTWATCH_WEBHOOK_SECRET'),
        'signature_header' => 'Nightwatch-Signature',
        'allowed_environments' => ['production'],
    ],

    'github' => [
        'token' => env('GITHUB_ISSUES_TOKEN'),
        'repository' => env('GITHUB_REPOSITORY'),
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
        'labels' => ['bug', 'production-bug', 'nightwatch'],
        'ignored_label' => 'nightwatch-ignored',
    ],

    'queue' => [
        'connection' => env('NIGHTWATCH_GITHUB_QUEUE_CONNECTION'),
        'name' => env('NIGHTWATCH_GITHUB_QUEUE', 'default'),
    ],

    'events' => [
        'issue.opened' => true,
        'issue.reopened' => true,
        'issue.resolved' => true,
        'issue.ignored' => true,
    ],

    'table' => 'nightwatch_github_issues',
];
