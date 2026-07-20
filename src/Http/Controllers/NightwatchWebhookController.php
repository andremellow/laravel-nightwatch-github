<?php

namespace Andremellow\NightwatchGithub\Http\Controllers;

use Andremellow\NightwatchGithub\Jobs\ProcessNightwatchWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;

class NightwatchWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('nightwatch-github.enabled')) {
            return response()->json(['message' => 'Webhook disabled.'], 404);
        }

        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $validator = Validator::make(is_array($data) ? $data : [], [
            'event' => ['required', 'string', 'in:issue.opened,issue.reopened,issue.resolved,issue.ignored'],
            'timestamp' => ['required', 'date'],
            'payload.issue.id' => ['required', 'uuid'],
            'payload.issue.ref' => ['required', 'integer'],
            'payload.issue.type' => ['required', 'string'],
            'payload.issue.title' => ['required', 'string'],
            'payload.issue.status' => ['required', 'string'],
            'payload.issue.priority' => ['nullable', 'string'],
            'payload.issue.url' => ['required', 'url', 'starts_with:https://nightwatch.laravel.com/'],
            'payload.issue.details' => ['nullable', 'array'],
            'payload.environment.name' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid payload.', 'errors' => $validator->errors()], 422);
        }

        $event = (string) $data['event'];
        $environment = strtolower((string) Arr::get($data, 'payload.environment.name'));
        $allowed = array_map('strtolower', config('nightwatch-github.nightwatch.allowed_environments', []));

        $enabledEvents = config('nightwatch-github.events', []);

        $environmentIsAllowed = in_array($event, ['issue.resolved', 'issue.ignored'], true)
            || in_array($environment, $allowed, true);

        if (! ($enabledEvents[$event] ?? false) || ! $environmentIsAllowed) {
            return response()->json(['accepted' => true, 'processed' => false], 202);
        }

        Bus::dispatch(new ProcessNightwatchWebhook($validator->validated()));

        return response()->json(['accepted' => true, 'processed' => true], 202);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = config('nightwatch-github.nightwatch.webhook_secret');
        $header = (string) config('nightwatch-github.nightwatch.signature_header');
        $signature = $request->header($header);

        if (! is_string($secret) || $secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
