<?php

namespace Andremellow\NightwatchGithub;

use Andremellow\NightwatchGithub\Contracts\GitHubIssues;
use Illuminate\Support\ServiceProvider;

class NightwatchGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nightwatch-github.php', 'nightwatch-github');
        $this->app->singleton(GitHubIssues::class, GitHubClient::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        $this->publishes([
            __DIR__.'/../config/nightwatch-github.php' => config_path('nightwatch-github.php'),
        ], 'nightwatch-github-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'nightwatch-github-migrations');
    }
}
