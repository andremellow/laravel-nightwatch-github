# Laravel Nightwatch GitHub Issues

[![Tests](https://github.com/andremellow/laravel-nightwatch-github/actions/workflows/tests.yml/badge.svg)](https://github.com/andremellow/laravel-nightwatch-github/actions/workflows/tests.yml)

Forward signed [Laravel Nightwatch](https://nightwatch.laravel.com) issue webhooks to GitHub Issues without running an intermediary service.

The package verifies every webhook with HMAC-SHA256, processes accepted events on a Laravel queue, creates or updates a corresponding GitHub Issue, and keeps Nightwatch issue state synchronized with GitHub. Production incidents are labeled `bug`, `production-bug`, and `nightwatch` by default.

## How it works

```text
Laravel Nightwatch
    -> signed HTTPS webhook
    -> your Laravel application
    -> HMAC signature verification
    -> queued job
    -> GitHub REST API
    -> synchronized GitHub Issue
```

The package handles these Nightwatch events:

| Nightwatch event | GitHub behavior |
| --- | --- |
| `issue.opened` | Creates a new issue, or comments on the existing mapped issue. |
| `issue.reopened` | Reopens the mapped issue and adds a comment. |
| `issue.resolved` | Closes the mapped issue and adds a comment. |
| `issue.ignored` | Closes the mapped issue, adds `nightwatch-ignored`, and comments. |

Only the `production` environment is processed by default. Other environments receive a successful `202 Accepted` response but do not create GitHub Issues.

## Requirements

- PHP 8.2 or later
- Laravel 12 or 13
- A persistent Laravel queue in production
- A GitHub fine-grained personal access token with access to the target repository
- A Laravel Nightwatch application with webhook access

## Installation

Once the package is available through Packagist, install it with Composer:

```bash
composer require andremellow/laravel-nightwatch-github
```

Before a Packagist release, Composer can install the development version directly from GitHub:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/andremellow/laravel-nightwatch-github"
        }
    ]
}
```

```bash
composer require andremellow/laravel-nightwatch-github:dev-main
```

Publish the configuration and migration:

```bash
php artisan vendor:publish --tag=nightwatch-github-config
php artisan vendor:publish --tag=nightwatch-github-migrations
php artisan migrate
```

Laravel package discovery registers the service provider automatically.

## GitHub token setup

Create a fine-grained personal access token in GitHub:

1. Open **GitHub > Settings > Developer settings > Personal access tokens > Fine-grained tokens**.
2. Select the account that owns the destination repository.
3. Restrict repository access to the repository that should receive Nightwatch issues.
4. Grant **Issues: Read and write** permission.
5. Copy the token and store it in your production secret manager.

The token is used only as a Bearer token for the GitHub REST API. It is never placed in an issue body or application log by this package.

Configure these environment variables in your deployment platform:

```dotenv
GITHUB_ISSUES_TOKEN=github_pat_...
GITHUB_REPOSITORY=owner/repository
```

For Laravel Cloud, add them under the production environment's variables or secrets. Do not commit real values to source control.

## Nightwatch webhook setup

Deploy the package before configuring Nightwatch so the webhook endpoint is publicly available.

The default endpoint is:

```text
https://your-application.example/api/webhooks/nightwatch
```

Then configure Nightwatch:

1. Open your application in the Nightwatch dashboard.
2. Open the application's **Issues** settings.
3. Find **Webhooks** and add a webhook.
4. Enter the public HTTPS endpoint shown above.
5. Save the webhook.
6. Open the webhook configuration and copy its signing secret.
7. Store that value in your deployment platform as `NIGHTWATCH_WEBHOOK_SECRET`.

```dotenv
NIGHTWATCH_WEBHOOK_SECRET=the-signing-secret-shown-by-nightwatch
```

Nightwatch sends a `Nightwatch-Signature` HTTP header. The header contains an HMAC-SHA256 signature calculated from the exact raw request body and the signing secret. The package calculates the same value and compares the two signatures with `hash_equals`. Invalid or missing signatures receive `401 Unauthorized` and are never queued.

Do not paste the signing secret into the webhook URL, GitHub, source code, logs, or issue bodies.

## Queue setup

Webhook processing is queued so the endpoint can acknowledge Nightwatch quickly and retry temporary GitHub failures safely.

Use a persistent queue driver in production, such as Redis or the database driver:

```dotenv
QUEUE_CONNECTION=database
NIGHTWATCH_GITHUB_QUEUE=default
```

Ensure a queue worker is running:

```bash
php artisan queue:work --queue=default
```

On Laravel Cloud, configure a worker cluster or background process for the selected queue.

The job tries five times with increasing delays. GitHub HTTP requests also retry temporary failures three times.

## Configuration

Publish `config/nightwatch-github.php` to customize the integration.

### Labels

The default labels are:

```php
'labels' => [
    'bug',
    'production-bug',
    'nightwatch',
],
```

The GitHub client creates a missing label before creating or updating an issue. `production-bug` is the stable label intended for filters and automated Codex triage.

You may replace the labels with your own names:

```php
'github' => [
    // ...
    'labels' => ['bug', 'production-bug', 'nightwatch'],
    'ignored_label' => 'nightwatch-ignored',
],
```

### Environments

Only production is enabled by default:

```php
'allowed_environments' => ['production'],
```

Environment matching is case-insensitive. To forward staging issues too:

```php
'allowed_environments' => ['production', 'staging'],
```

### Route

The default route is `POST /api/webhooks/nightwatch`. Change it with:

```dotenv
NIGHTWATCH_GITHUB_WEBHOOK_PATH=api/integrations/nightwatch
```

The route uses the `api` and `throttle:60,1` middleware by default. You can change the middleware in the published configuration.

### Disable the integration

```dotenv
NIGHTWATCH_GITHUB_ENABLED=false
```

Disabled endpoints return `404 Not Found`.

### GitHub Enterprise Server

Override the GitHub API base URL:

```dotenv
GITHUB_API_URL=https://github.example.com/api/v3
```

## Issue contents

Created issues include:

- the Nightwatch issue reference
- environment, type, and priority
- exception class and message when available
- application file and line when available
- a direct link to the Nightwatch issue
- a hidden Nightwatch UUID marker for recovery and deduplication

The package stores the Nightwatch UUID and GitHub issue number in `nightwatch_github_issues`. This mapping prevents duplicates and makes state synchronization deterministic. If the mapping row is lost, the package searches GitHub for the hidden UUID marker before creating another issue.

The GitHub issue deliberately does not include request headers, authorization tokens, cookies, request bodies, database bindings, or a complete stack trace. Use the authenticated Nightwatch link for sensitive operational context.

## Security model

The webhook endpoint is intentionally unauthenticated by Laravel sessions or Sanctum because Nightwatch is the caller. Its authentication mechanism is the signed request.

Security controls include:

- HMAC-SHA256 verification over the exact raw body
- constant-time signature comparison
- strict event and payload validation
- HTTPS Nightwatch URL validation
- production-only processing by default
- rate limiting
- scoped GitHub credentials
- queued processing with bounded retries
- database and GitHub-marker deduplication
- no secret values in generated issues

If the signing secret is missing, every webhook request is rejected. If the GitHub token or repository is missing, the queued job fails and is retried rather than silently discarding the event.

Rotate a compromised secret immediately in Nightwatch and your deployment platform. Rotate a compromised GitHub token in GitHub and update the deployment secret.

## Codex triage workflow

This package creates the reliable bridge from Nightwatch to GitHub. A separate Codex automation can monitor open GitHub Issues with both `production-bug` and `nightwatch`, then:

1. inspect the issue details and linked repository code
2. comment with a root-cause analysis
3. suggest a safe remediation
4. prepare a pull request when authorized

The Codex automation is intentionally separate from this PHP package. Installing a Composer package must not silently install or control desktop automations.

## Testing locally

Install dependencies and run the package test suite:

```bash
composer install
composer test
composer format:test
```

Tests use Pest, Orchestra Testbench, SQLite in memory, queue fakes, and Laravel HTTP client fakes. No test sends a request to Nightwatch or GitHub.

To generate a valid signature manually:

```php
$body = json_encode($payload, JSON_THROW_ON_ERROR);
$signature = hash_hmac('sha256', $body, $secret);
```

Send the signature as the `Nightwatch-Signature` header. Calculate it from the exact bytes sent as the request body; reformatting the JSON after signing changes the signature.

## Troubleshooting

### Webhook returns 401

- Confirm `NIGHTWATCH_WEBHOOK_SECRET` matches the signing secret displayed for that webhook in Nightwatch.
- Clear cached configuration after changing secrets: `php artisan config:clear`.
- Ensure a proxy is not rewriting the request body.

### Webhook returns 202 but no issue appears

- Confirm the Nightwatch environment name is listed in `allowed_environments`.
- Confirm a queue worker is running.
- Inspect failed jobs with `php artisan queue:failed`.
- Confirm `GITHUB_REPOSITORY` uses the `owner/repository` format.
- Confirm the GitHub token has Issues read/write access to that repository.

### Labels are missing

The token must be allowed to manage issues in the repository. The package creates missing configured labels through the GitHub Issues API.

### Duplicate issues

Confirm the package migration has run and the application uses a persistent database. Do not delete the hidden `nightwatch-issue-id` marker from issue bodies.

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the development workflow.

## Security vulnerabilities

Do not report security vulnerabilities in public GitHub Issues. Follow [SECURITY.md](SECURITY.md).

## License

Laravel Nightwatch GitHub Issues is open-source software licensed under the [MIT license](LICENSE.md).
