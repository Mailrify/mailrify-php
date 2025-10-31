# Contributing to the Mailrify PHP SDK

We love contributions! Please follow these guidelines to keep the project healthy and predictable.

1. **Discussions first** – Open an issue describing your proposed change before starting large work.
2. **Security** – Never include secrets in code or tests. Report vulnerabilities following [`SECURITY.md`](./SECURITY.md).
3. **Coding standards** – Run `composer format` (php-cs-fixer dry run) and `composer analyse` before submitting a PR. All code must pass unit tests and, when possible, integration tests.
4. **Tests** – Add or update unit tests for every behaviour change. Integration tests should remain opt-in via environment variables.
5. **Commits** – Keep commits small, focused, and with descriptive messages. Reference related issues when applicable.

## Development Workflow

```bash
composer install
composer format
composer analyse
composer test:unit
```

If you have access to a test Mailrify environment, also run:

```bash
MAILRIFY_API_KEY=... composer test:integration
```

Thank you for helping us improve Mailrify!
