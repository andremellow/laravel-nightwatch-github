<?php

namespace Andremellow\NightwatchGithub\Tests;

use Andremellow\NightwatchGithub\NightwatchGithubServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [NightwatchGithubServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('nightwatch-github.route.middleware', []);
        $app['config']->set('nightwatch-github.nightwatch.webhook_secret', 'test-secret');
        $app['config']->set('nightwatch-github.github.repository', 'owner/repository');
        $app['config']->set('nightwatch-github.github.token', 'github-token');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('nightwatch_github_issues', function (Blueprint $table): void {
            $table->id();
            $table->uuid('nightwatch_issue_id')->unique();
            $table->string('github_repository');
            $table->unsignedBigInteger('github_issue_number');
            $table->string('last_event');
            $table->timestamps();
        });
    }
}
