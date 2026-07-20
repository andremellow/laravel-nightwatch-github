<?php

use Andremellow\NightwatchGithub\Http\Controllers\NightwatchWebhookController;
use Illuminate\Support\Facades\Route;

Route::post(
    config('nightwatch-github.route.uri'),
    NightwatchWebhookController::class,
)->middleware(config('nightwatch-github.route.middleware'))
    ->name(config('nightwatch-github.route.name'));
