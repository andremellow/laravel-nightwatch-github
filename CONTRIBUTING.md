# Contributing

Thank you for considering a contribution.

## Development setup

```bash
git clone git@github.com:andremellow/laravel-nightwatch-github.git
cd laravel-nightwatch-github
composer install
composer test
composer format:test
```

## Pull requests

- Add or update Pest coverage for behavioral changes.
- Fake every outgoing GitHub request with `Http::fake()`.
- Never include real Nightwatch payloads, signing secrets, GitHub tokens, authorization headers, cookies, or personal data in fixtures.
- Keep webhook acknowledgment fast and move external work to queued jobs.
- Preserve backward compatibility for published configuration keys.
- Run the complete test and formatting suites before opening a pull request.

Open pull requests ready for review unless the work is intentionally incomplete.
