# Security Policy

## Supported versions

Security fixes are provided for the latest released version.

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability. Use GitHub's private vulnerability reporting feature on this repository.

Include the affected version, impact, reproduction steps, and any proposed mitigation. Do not include production secrets or personal data.

## Secret handling

This package requires a Nightwatch signing secret and a GitHub token. Store both values only in a deployment secret manager. Never commit them, include them in URLs, or expose them in logs and issue bodies.
