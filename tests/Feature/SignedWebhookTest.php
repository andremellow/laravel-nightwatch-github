<?php

use Andremellow\NightwatchGithub\Jobs\ProcessNightwatchWebhook;
use Illuminate\Support\Facades\Queue;

it('accepts a valid signed production webhook and queues it', function () {
    Queue::fake();
    $body = json_encode(nightwatchPayload(), JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    $this->call('POST', '/api/webhooks/nightwatch', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_NIGHTWATCH_SIGNATURE' => $signature,
    ], $body)->assertAccepted()->assertJson([
        'accepted' => true,
        'processed' => true,
    ]);

    Queue::assertPushed(ProcessNightwatchWebhook::class);
});

it('rejects a webhook with an invalid signature', function () {
    Queue::fake();
    $body = json_encode(nightwatchPayload(), JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/webhooks/nightwatch', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_NIGHTWATCH_SIGNATURE' => 'invalid',
    ], $body)->assertUnauthorized();

    Queue::assertNothingPushed();
});

it('accepts but does not queue non-production events', function () {
    Queue::fake();
    $body = json_encode(nightwatchPayload(environment: 'staging'), JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    $this->call('POST', '/api/webhooks/nightwatch', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_NIGHTWATCH_SIGNATURE' => $signature,
    ], $body)->assertAccepted()->assertJson([
        'accepted' => true,
        'processed' => false,
    ]);

    Queue::assertNothingPushed();
});

it('accepts a resolved event without environment metadata', function () {
    Queue::fake();
    $payload = nightwatchPayload('issue.resolved');
    unset($payload['payload']['environment']);
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    $this->call('POST', '/api/webhooks/nightwatch', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_NIGHTWATCH_SIGNATURE' => $signature,
    ], $body)->assertAccepted()->assertJson([
        'accepted' => true,
        'processed' => true,
    ]);

    Queue::assertPushed(ProcessNightwatchWebhook::class);
});
