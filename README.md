# Mailrify PHP SDK

[![CI](https://github.com/mailrify/mailrify-php/actions/workflows/ci.yml/badge.svg)](https://github.com/mailrify/mailrify-php/actions/workflows/ci.yml)
[![Release](https://github.com/mailrify/mailrify-php/actions/workflows/release.yml/badge.svg)](https://github.com/mailrify/mailrify-php/actions/workflows/release.yml)

The official PHP SDK for [Mailrify](https://mailrify.com), a transactional and marketing email platform. This library provides a type-safe, developer-friendly wrapper around the Mailrify REST API, generated from the upstream [mailrify-openapi](https://github.com/Mailrify/mailrify-openapi) specification.

## Requirements

- PHP **8.1** or newer
- Composer
- A Mailrify API key with bearer-token access

## Installation

```bash
composer require mailrify/mailrify-php
```

## Quickstart

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Mailrify\Sdk\Client;

$client = Client::create([
    'apiKey'  => 'YOUR_API_KEY',
]);

$response = $client->emails()->send([
    'from'    => 'Your app <no-reply@yourdomain.com>',
    'to'      => 'client@example.com',
    'subject' => 'Welcome to Mailrify 🚀',
    'html'    => '<p>It works! 👋</p>',
    'text'    => 'It works!'
]);

echo $response->emailId;
```

## Configuration

`Client::create()` accepts the following options:

| Option | Type | Default | Description |
| ------ | ---- | ------- | ----------- |
| `apiKey` | `string` | – (required) | Mailrify bearer token. Falls back to `MAILRIFY_API_KEY`. |
| `baseUrl` | `string` | `https://app.mailrify.com/api` | Override for staging/self-hosted deployments. |
| `timeout` | `float` | `10.0` | Request timeout in seconds. |
| `maxRetries` | `int` | `2` | Automatic retries for idempotent requests (`GET`, `HEAD`). |
| `debug` | `bool` | `false` | Enable sanitized HTTP request logging. |
| `userAgent` | `string` | `mailrify-php/<version>` | Overrides the default User-Agent header. |
| `psrClient` | `Psr\Http\Client\ClientInterface` | – | Inject your own PSR-18 client. Requires factories below. |
| `requestFactory` | `Psr\Http\Message\RequestFactoryInterface` | – | Required when `psrClient` is provided. |
| `streamFactory` | `Psr\Http\Message\StreamFactoryInterface` | – | Required when `psrClient` is provided. |
| `symfonyClient` | `Symfony\Contracts\HttpClient\HttpClientInterface` | – | Provide a custom Symfony HttpClient instance. |

By default the SDK uses `symfony/http-client` with sensible defaults and automatic retry logic.

## API Coverage

The SDK mirrors the Mailrify API grouped by resource (methods ending with `All` represent list operations—`list` is a reserved keyword in PHP):

| SDK Method | HTTP | Endpoint |
| ---------- | ---- | -------- |
| `$client->domains()->listAll()` | `GET` | `/v1/domains` |
| `$client->domains()->create()` | `POST` | `/v1/domains` |
| `$client->domains()->get($id)` | `GET` | `/v1/domains/{id}` |
| `$client->domains()->delete($id)` | `DELETE` | `/v1/domains/{id}` |
| `$client->domains()->verify($id)` | `PUT` | `/v1/domains/{id}/verify` |
| `$client->emails()->send()` | `POST` | `/v1/emails` |
| `$client->emails()->listAll()` | `GET` | `/v1/emails` |
| `$client->emails()->get($emailId)` | `GET` | `/v1/emails/{emailId}` |
| `$client->emails()->updateSchedule()` | `PATCH` | `/v1/emails/{emailId}` |
| `$client->emails()->cancel()` | `POST` | `/v1/emails/{emailId}/cancel` |
| `$client->emails()->batch()` | `POST` | `/v1/emails/batch` |
| `$client->contacts()->listAll()` | `GET` | `/v1/contactBooks/{contactBookId}/contacts` |
| `$client->contacts()->create()` | `POST` | `/v1/contactBooks/{contactBookId}/contacts` |
| `$client->contacts()->get()` | `GET` | `/v1/contactBooks/{contactBookId}/contacts/{contactId}` |
| `$client->contacts()->update()` | `PATCH` | `/v1/contactBooks/{contactBookId}/contacts/{contactId}` |
| `$client->contacts()->upsert()` | `PUT` | `/v1/contactBooks/{contactBookId}/contacts/{contactId}` |
| `$client->contacts()->delete()` | `DELETE` | `/v1/contactBooks/{contactBookId}/contacts/{contactId}` |
| `$client->campaigns()->create()` | `POST` | `/v1/campaigns` |
| `$client->campaigns()->get()` | `GET` | `/v1/campaigns/{campaignId}` |
| `$client->campaigns()->schedule()` | `POST` | `/v1/campaigns/{campaignId}/schedule` |
| `$client->campaigns()->pause()` | `POST` | `/v1/campaigns/{campaignId}/pause` |
| `$client->campaigns()->resume()` | `POST` | `/v1/campaigns/{campaignId}/resume` |

All responses return rich DTOs defined in `src/Models/` for strong typing.

## Error Handling

All errors extend `Mailrify\Sdk\Exceptions\MailrifyException`:

- `AuthException` — authentication/authorization failures (401/403)
- `RateLimitException` — throttling responses (429) with optional `retryAfter`
- `ApiException` — other 4xx/5xx responses with detailed payloads
- `ValidationException` — thrown client-side before making a request
- `NetworkException` — network/transport problems after retries are exhausted

```php
try {
    $client->emails()->send([...]);
} catch (Mailrify\Sdk\Exceptions\RateLimitException $rateLimit) {
    sleep($rateLimit->getRetryAfter() ?? 1);
} catch (Mailrify\Sdk\Exceptions\MailrifyException $error) {
    // Centralized logging
}
```

## Testing

Unit tests mock the HTTP layer to guarantee deterministic behavior. Integration tests exercise the live API and are **skipped** unless `MAILRIFY_API_KEY` (and optionally `MAILRIFY_BASE_URL`) are set.

```bash
composer test:unit
MAILRIFY_API_KEY=your-key \
MAILRIFY_INTEGRATION_FROM=sender@example.com \
MAILRIFY_INTEGRATION_TO=recipient@example.com \
composer test:integration
```

`MAILRIFY_INTEGRATION_TO` accepts a single email or a comma-separated list. Integration tests are skipped automatically if any required environment variable is missing.

## Development

1. Clone the repository and install dependencies:
   ```bash
   composer install
   ```
2. Run style + static analysis:
   ```bash
   composer format
   composer analyse
   ```
3. Run tests:
   ```bash
   composer test:unit
   ```
4. Regenerate types after updating the OpenAPI spec:
   ```bash
   node scripts/sync-openapi.mjs # optional helper
   ```

## Security

Please review [`SECURITY.md`](./SECURITY.md) for the responsible disclosure process. Never commit secrets—use GitHub Actions secrets and enable MFA for npm/Packagist accounts.

## Contributing

Contributions are welcome! Please open an issue or pull request. By contributing you agree to license your work under the MIT License. A Contributor License Agreement is not currently required.

## License

This library is released under the [MIT License](./LICENSE).
