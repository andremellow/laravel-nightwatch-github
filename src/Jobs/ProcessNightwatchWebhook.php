<?php

namespace Andremellow\NightwatchGithub\Jobs;

use Andremellow\NightwatchGithub\Support\SyncNightwatchIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNightwatchWebhook implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 120, 300];

    /** @param array<string, mixed> $webhook */
    public function __construct(public readonly array $webhook)
    {
        $this->onConnection(config('nightwatch-github.queue.connection'));
        $this->onQueue(config('nightwatch-github.queue.name'));
    }

    public function handle(SyncNightwatchIssue $sync): void
    {
        $sync->handle($this->webhook);
    }
}
