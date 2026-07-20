<?php

use Andremellow\NightwatchGithub\GitHubClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('creates missing labels and a production issue', function () {
    Http::fake(function (Request $request) {
        if ($request->method() === 'GET' && str_contains($request->url(), '/labels/')) {
            return Http::response([], 404);
        }

        if ($request->method() === 'POST' && str_ends_with($request->url(), '/labels')) {
            return Http::response(['name' => 'label'], 201);
        }

        return Http::response(['number' => 73], 201);
    });

    $number = app(GitHubClient::class)->create(
        '[Production] Failure',
        'Body',
        ['bug', 'production-bug', 'nightwatch'],
    );

    expect($number)->toBe(73);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/owner/repository/issues'
        && $request['labels'] === ['bug', 'production-bug', 'nightwatch']);
});

it('finds an existing issue by its hidden nightwatch marker', function () {
    Http::fake([
        'api.github.com/search/issues*' => Http::response([
            'items' => [['number' => 73]],
        ]),
    ]);

    expect(app(GitHubClient::class)->findByNightwatchId('9d4e2c1a-1234-4678-abcd-ef0123456789'))->toBe(73);
});
