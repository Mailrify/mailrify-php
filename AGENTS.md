# Instructions for Building the **Mailrify** SDKs (Node.js & PHP)

## Introduction

Mailrify is a transactional and marketing email service, similar to SendGrid, Mailgun, Postmark, and Resend. The goal of this project is to create a **Node.js SDK** for Mailrify’s REST API that is secure, developer‑friendly, and fully aligned with the upstream [`mailrify-openapi`](https://github.com/Mailrify/mailrify-openapi) (OpenAPI 3.0) specification. The SDK must feature clear naming, minimal dependencies, robust error handling, and **comprehensive automated tests** (unit + integration against a live Mailrify API). It will be published publicly on **GitHub** and **npm** with tight security and CI/CD in place.

---

## Non‑Negotiable Requirements (TL;DR)

- **OpenAPI is the single source of truth.** Implement every operation defined in the `mailrify-openapi` repository.
- **Auth:** Bearer tokens only (as per spec). Auto‑inject `Authorization: Bearer <token>`.
- **Language:** TypeScript first (ship ESM + CJS if feasible); Node.js ≥ 18 (native `fetch` available).
- **Dependencies:** Minimal. Prefer native `fetch`; only add third‑party libs when they add real value.
- **DX:** Human‑friendly names, excellent docs, typed inputs/outputs, clear errors.
- **Tests:** 100% of methods have **unit tests** (mocked HTTP) + **integration tests** (live API).
- **Security:** No secrets in repo, 2FA for npm, locked CI secrets, least‑privilege tokens.
- **CI/CD:** GitHub Actions for lint → typecheck → build → unit tests → (optional) integration tests → publish.
- **Release:** Semantic Versioning, changelog, signed tags (optional), npm + GitHub Releases.

---

## Project Structure

```
mailrify-sdk/
├─ src/
│  ├─ client.ts           # Core HTTP client wrapper (fetch, headers, baseURL, retries, timeouts)
│  ├─ index.ts            # Entry point: exports Client and resource namespaces
│  ├─ config.ts           # Config schema/types; validation of apiKey, baseUrl, timeouts
│  ├─ errors.ts           # MailrifyError, HTTPError, ValidationError, RateLimitError
│  ├─ interceptors.ts     # Optional: request/response interceptors (e.g., tracing, retries)
│  ├─ utils/
│  │  ├─ casing.ts        # camelCase <-> snake_case helpers if needed
│  │  └─ schema.ts        # lightweight runtime guards (optional) / zod types (optional)
│  ├─ resources/
│  │  ├─ emails.ts        # /v1/emails, /v1/emails/batch, /v1/emails/{id}, etc.
│  │  ├─ domains.ts       # /v1/domains, /v1/domains/{id}, /verify
│  │  └─ campaigns.ts     # /v1/campaigns, /schedule, /pause, /resume
│  └─ types/
│     └─ openapi.ts       # TS types generated from the upstream OpenAPI spec (models, params, responses)
├─ test/
│  ├─ unit/               # Jest/Vitest unit tests with HTTP mocking
│  ├─ integration/        # Live API tests (skipped unless env vars are present)
│  └─ fixtures/           # Sample payloads and responses
├─ .github/workflows/
│  ├─ ci.yml              # Lint, typecheck, unit tests, build
│  └─ release.yml         # Publish to npm on tag, create GitHub Release
├─ openapi.yaml           # Cached copy fetched via sync script (git-ignored)
├─ spec-version.json      # Timestamped record of the synced OpenAPI version/source
├─ package.json
├─ README.md
├─ LICENSE
├─ tsconfig.json
├─ .eslintrc.cjs          # or .eslintrc.json
├─ .prettierrc
├─ .npmignore
└─ .gitignore
```

---

## Implementation Blueprint

### 1) Types from OpenAPI

- Use a generator to produce **TypeScript types** from the upstream spec (e.g., `openapi-typescript` or OpenAPI Generator TS types only). Commit generated types to `src/types/openapi.ts`.
  - `generate:types`: `node scripts/sync-openapi.mjs && openapi-typescript openapi.yaml -o src/types/openapi.ts`.
  - Set `MAILRIFY_OPENAPI_URL` (and optionally `MAILRIFY_OPENAPI_VERSION`) to target a specific tag before regenerating types.
  - Ensure `spec-version.json` captures the version and source after each sync (commit the updated file).
- Map OpenAPI schemas to idiomatic TS types (union enums, nullable fields as `string | null`, etc.).
- Prefer exposing **typed interfaces** to users rather than opaque `any`.

### 2) HTTP Layer

- Prefer **native `fetch`** (Node ≥ 18). Create a thin wrapper that applies:
  - Base URL (default from spec; override via config).
  - `Authorization: Bearer <apiKey>` header.
  - `Content-Type: application/json` for JSON bodies.
  - Optional: per‑request timeout via `AbortController`.
  - Optional: basic retry for idempotent requests (GET/HEAD) with exponential backoff.
- Centralize response handling:
  - For 2xx: parse JSON and return typed result.
  - For non‑2xx: throw `MailrifyError` with `status`, `code` (if available), `message`, and `details` (response body).

### 3) Client and Resources

- Export a single entry **`Client`**:
  ```ts
  const mailrify = new Client({ apiKey: process.env.MAILRIFY_API_KEY! });
  // Grouped resources (preferred for discoverability)
  await mailrify.emails.send({ to, from, subject, text, html, attachments });
  const message = await mailrify.emails.get(emailId);
  const list = await mailrify.emails.list({ page, limit, startDate, endDate, domainId });
  await mailrify.emails.cancel(emailId);
  ```
- Resource modules mirror OpenAPI paths:
  - **Domains**: list, create, get, delete, verify.
  - **Emails**: create (send), list, get, patch (schedule), cancel, batch send.
  - **ContactBooks/Contacts**: upsert, get, list, delete (as defined).
  - **Campaigns**: create, get, schedule, pause, resume.
- Method names are **human‑friendly** and verbs reflect behavior: `send`, `list`, `get`, `create`, `update`, `delete`, `schedule`, `pause`, `resume`, `cancel`.

### 4) Input & Output Shapes

- Public method params accept **camelCased** properties; adapter translates to API field names if spec uses different casing.
- Validate **required** fields early and throw `ValidationError` with a helpful message before firing the request.
- Return **typed results** mapped from OpenAPI responses (e.g., `SendEmailResponse`).

### 5) Error Model

- `MailrifyError extends Error` with fields:
  - `name = 'MailrifyError'`
  - `status: number`
  - `code?: string`
  - `details?: unknown` (raw error payload)
  - `requestId?: string` (if API returns it)
- Derive specializations (`AuthError`, `RateLimitError`, etc.) if useful. Never leak secrets in error messages.

### 6) Security Posture

- No secrets committed. `.env*` in `.gitignore` and not referenced in code samples beyond placeholders.
- npm account **2FA enabled**. GitHub Actions use **repository secrets**; workflows run on trusted branches only.
- Avoid logging request bodies by default; add an opt‑in `debug` flag that redacts `Authorization` and obvious PII.
- Keep dependencies minimal and monitored (`npm audit`, Dependabot).

---

## Testing Strategy

### Unit Tests (mocked HTTP)

- Framework: **Jest** (or **Vitest**). Use **Nock** or **MSW** to mock HTTP.
- Coverage: every public method. Assert:
  - Correct path/method/query/body/headers.
  - Proper handling of success and error responses.
  - Input validation errors are thrown pre‑request.
  - Auth header injection is correct.

### Integration Tests (live API)

- Opt‑in only; skipped by default when `MAILRIFY_API_KEY` is absent.
- Use a dedicated `MAILRIFY_BASE_URL` if testing against staging.
- Test happy paths minimally (smoke tests):
  - Emails: send → fetch by id → list → cancel/schedule (if applicable).
  - Domains: create → verify → get → delete (use disposable domains or a sandbox if available).
  - Campaigns: create → schedule → pause → resume → get.
- Clean up any created resources in `afterAll` hooks.
- In CI, run integration job only when secrets are available (e.g., on `main` or tagged releases).

---

## CI/CD (GitHub Actions)

### `ci.yml` (pull requests and main)

- Triggers: PRs and pushes.
- Jobs:
  1. **setup**: `actions/setup-node@v4` (Node 20), cache npm.
  2. **lint**: ESLint + Prettier check.
  3. **typecheck**: `tsc --noEmit`.
  4. **test:unit**: `npm run test:unit -- --coverage`.
  5. **build**: `npm run build` (ensure `dist/` is produced).
  6. (Optional) **test:integration** only on `main` with secrets available.

### `release.yml` (tagged releases)

- Trigger: push tag `v*.*.*`.
- Steps:
  1. Repeat CI steps (lint/typecheck/build/tests).
  2. **npm publish** using `NPM_TOKEN` (with 2FA policy handled).
  3. Create GitHub Release with changelog notes (auto‑generate from commits or use `changesets`/`semantic-release`).

---

## Packaging & Distribution

### Package.json

- `"name"`: `mailrify`.
- `"version"`: SemVer.
- `"type"`: `"module"` (ESM). Provide CJS via dual build if needed.
- `"main"`: `./dist/index.cjs` (if dual output).
- `"module"`: `./dist/index.js` (ESM).
- `"types"`: `./dist/index.d.ts`.
- `"exports"`: conditional exports for `import`/`require`.
- `"files"`: `dist`, `README.md`, `LICENSE` (avoid shipping tests/config).
- `"engines"`: `{ "node": ">=18" }`.
- Scripts:
  - `build`: `tsc -p tsconfig.build.json` (or rollup if bundling).
  - `lint`: `eslint .` and `prettier --check .`.
  - `test`: maps to unit tests by default.
  - `test:unit`, `test:integration` (the latter guarded by env vars).
  - `generate:types`: `node scripts/sync-openapi.mjs && openapi-typescript openapi.yaml -o src/types/openapi.ts`.

### Publishing

- **npm**: Use an automation token stored as `NPM_TOKEN`. Enable 2FA for auth or publish.
- **GitHub**: Create a Release with notes, link to docs, and changelog entries.
- Document how to install:
  ```bash
  npm i mailrify
  # or
  pnpm add mailrify
  ```

---

## Documentation

### README.md

- **What is Mailrify?** Short description.
- **Install** and **Requirements** (Node 18+).
- **Quickstart**:

  ```ts
  import { Client } from 'mailrify';

  const mailrify = new Client({ apiKey: process.env.MAILRIFY_API_KEY! });

  await mailrify.emails.send({
    from: 'you@yourdomain.com',
    to: 'customer@example.com',
    subject: 'Welcome!',
    text: 'Hello, this is a test email.',
  });
  ```

- **Auth**: Bearer token; set via constructor. Optional `baseUrl`, `timeout`, `debug`.
- **API Coverage**: Table mapping SDK methods → OpenAPI paths.
- **Errors**: How to catch `MailrifyError`, typical status codes.
- **Integration Tests**: How to run with env vars.
- **Contributing**: PRs welcome; coding standards; DCO (optional).
- **License**: MIT.

### Inline Docs

- TSDoc/JSDoc for all public symbols. Describe params, return types, thrown errors, and examples.

### Examples

- Add `examples/` with runnable scripts (using env vars) for common flows:
  - Send a single email
  - Batch send
  - List emails with filters
  - Create/schedule/pause/resume a campaign
  - Manage domains

---

## Quality & Maintenance

- **Style**: ESLint + Prettier; no unused vars; strict TS config.
- **Type Safety**: `strict: true`; never expose `any` in public API.
- **Backwards Compatibility**: Avoid breaking changes in minor/patch versions. Deprecate before removal.
- **Observability**: Optional hooks for logging/tracing via interceptors; redaction on by default.
- **Performance**: Avoid heavy deps; lazy import optional features; keep the HTTP layer lean.
- **Monitoring**: Enable Dependabot; run `npm audit` in CI.
- **Issue Templates**: Bug report and feature request templates in `.github/ISSUE_TEMPLATE`.
- **Security Policy**: Add `SECURITY.md` with contact for vulnerability disclosures.

---

## Implementation Checklist

- [ ] Sync the upstream spec; generate TS types.
- [ ] Implement `Client` and HTTP wrapper.
- [ ] Implement **Domains** resource: list/create/get/delete/verify.
- [ ] Implement **Emails** resource: send/list/get/schedule/cancel/batch.
- [ ] Implement **ContactBooks/Contacts** as per spec (create/list/get/patch/put/delete).
- [ ] Implement **Campaigns**: create/get/schedule/pause/resume.
- [ ] Add comprehensive unit tests for every method (mocked HTTP).
- [ ] Add smoke‑level integration tests (guarded by env vars).
- [ ] Add ESLint/Prettier configs; enforce in CI.
- [ ] Add GitHub Actions workflows: `ci.yml`, `release.yml`.
- [ ] Write README with quickstart, API coverage, and examples.
- [ ] Configure package for ESM/CJS, types, files, exports.
- [ ] Prepare LICENSE (MIT) and CONTRIBUTING guidelines.
- [ ] Set up Dependabot and `npm audit` job.
- [ ] Dry‑run publish (`npm pack`), verify contents, then tag & publish.

---

## Example TypeScript Signatures (Illustrative)

```ts
// src/resources/emails.ts
export interface SendEmailRequest {
  to: string | string[];
  from: string;
  subject?: string;
  templateId?: string;
  variables?: Record<string, string>;
  replyTo?: string | string[];
  cc?: string | string[];
  bcc?: string | string[];
  text?: string | null;
  html?: string | null;
  headers?: Record<string, string>;
  attachments?: { filename: string; content: string }[];
  scheduledAt?: string; // ISO-8601
  inReplyToId?: string | null;
}

export interface SendEmailResponse {
  emailId: string;
}

export class EmailsAPI {
  constructor(private readonly client: HttpClient) {}

  async send(body: SendEmailRequest): Promise<SendEmailResponse> {
    // validate required `to`, `from`
    return this.client.post('/v1/emails', body);
  }

  async get(emailId: string) {
    return this.client.get(`/v1/emails/${encodeURIComponent(emailId)}`);
  }

  async list(params?: {
    page?: string;
    limit?: string;
    startDate?: string; // ISO-8601
    endDate?: string; // ISO-8601
    domainId?: string | string[];
  }) {
    return this.client.get('/v1/emails', { query: params });
  }

  async cancel(emailId: string) {
    return this.client.post(`/v1/emails/${encodeURIComponent(emailId)}/cancel`);
  }

  async schedule(emailId: string, scheduledAt: string) {
    return this.client.patch(`/v1/emails/${encodeURIComponent(emailId)}`, { scheduledAt });
  }

  async batch(items: SendEmailRequest[]) {
    return this.client.post('/v1/emails/batch', items);
  }
}
```

_(The actual shapes must match the OpenAPI spec exactly.)_

---

## Security Notes Recap

- Never commit credentials. Use env vars (`MAILRIFY_API_KEY`, `MAILRIFY_BASE_URL`).
- Redact `Authorization` and obvious PII in any optional debug logs.
- Use npm 2FA; lock CI secrets; restrict release job to protected branches/tags.
- Keep dependency surface small and updated; remove unused packages.

---

## Release Process (Example)

1. Merge to `main` with passing CI.
2. Tag: `git tag v1.0.0 && git push --tags`.
3. `release.yml` runs: builds, tests, **publishes to npm**, creates GitHub Release.
4. Verify package on npm (`npm info mailrify`) and test a fresh install.
5. Announce and document any breaking changes in changelog for future releases.

---

## Final Notes

- The SDK must **mirror the OpenAPI** faithfully while providing an idiomatic, pleasant Node.js developer experience.
- Favor clarity over cleverness. Make common tasks (sending an email) trivial; make advanced tasks possible.
- Keep tests fast, deterministic, and meaningful. Integration tests are opt‑in and clean up after themselves.
- Maintain **consistency**, **security**, and **simplicity** across the codebase.
- Never modify the cached `openapi.yaml`; refresh it via the sync script when needed.

**Outcome:** A production‑ready, secure, and well‑tested Mailrify Node.js SDK published on GitHub and npm, with great DX and complete coverage of the OpenAPI 3.0 spec.


---

# Instructions for Building the **Mailrify** PHP SDK

## Introduction

This section adapts the same non‑negotiable principles used for the Node.js SDK to a **hand‑crafted PHP SDK** (no code generation) that fully covers the upstream [`mailrify-openapi`](https://github.com/Mailrify/mailrify-openapi) (OpenAPI 3.0) specification while delivering excellent DX, security, and reliability. The SDK targets **PHP 8.1+** and will be published on **GitHub** and **Packagist**.

---

## Non‑Negotiable Requirements (TL;DR)

- **OpenAPI is the single source of truth.** Implement every Mailrify operation by hand to match the spec.
- **Auth:** Bearer tokens only. Auto‑inject `Authorization: Bearer <token>`.
- **Runtime:** PHP **8.1+** (typed properties, enums, readonly, first‑class callable syntax, etc.).
- **Dependencies:** Minimal. **Default transport:** `symfony/http-client` (fast, retries, HTTP/2). **Allow PSR‑18 client injection** for advanced users (e.g., Guzzle 7/8).
- **DX:** Human‑friendly method names, clear typed DTOs, precise exceptions, helpful docs.
- **Tests:** 100% of public methods covered by **unit tests** (mocked HTTP) + **opt‑in integration tests** (live API via env). 
- **Security:** No secrets in repo, 2FA on GitHub/Packagist, least‑privilege CI secrets, redact sensitive logs.
- **CI/CD:** GitHub Actions for csfix → static analysis → tests → build; tagged releases publish to Packagist.
- **Versioning:** SemVer, changelog, signed tags optional.

> Rationale: `symfony/http-client` is modern, fast, and has built‑in retry/backoff for idempotent requests. We still keep the transport **pluggable** via PSR‑18 so teams preferring Guzzle (de‑facto standard) can inject it without forking.

---

## Package Layout

```
mailrify-php/
├─ src/
│  ├─ Client.php               # Entry point; holds config, auth, http client
│  ├─ Config.php               # Value object for apiKey, baseUrl, timeouts, retries
│  ├─ Http/
│  │  ├─ HttpClientFactory.php # Creates Symfony HttpClient with sane defaults
│  │  ├─ Psr18Adapter.php      # (Optional) Bridge to PSR‑18 clients (Guzzle, etc.)
│  │  └─ Middleware.php        # (Optional) small hooks (headers, idempotency keys)
│  ├─ Exceptions/
│  │  ├─ MailrifyException.php
│  │  ├─ ApiException.php      # carries status, code, requestId, response body
│  │  ├─ AuthException.php
│  │  ├─ RateLimitException.php
│  │  └─ ValidationException.php
│  ├─ Resources/
│  │  ├─ EmailsApi.php         # /v1/emails, /batch, /{id}, /{id}/cancel, etc.
│  │  ├─ DomainsApi.php        # /v1/domains, /{id}, /verify
│  │  ├─ CampaignsApi.php      # /v1/campaigns, /schedule, /pause, /resume
│  │  └─ ... (Contacts, Lists, etc. per spec)
│  ├─ Models/
│  │  ├─ Email.php             # readonly DTOs with fromArray()/toArray()
│  │  ├─ Domain.php
│  │  ├─ Campaign.php
│  │  └─ Enums/EmailStatus.php # PHP 8.1 enums for constrained values
│  └─ Utils/
│     ├─ Arr.php               # tiny helpers (safe get, filter nulls)
│     └─ Json.php              # robust json encode/decode with exceptions
├─ tests/
│  ├─ Unit/                    # PHPUnit or Pest with mocked HTTP
│  ├─ Integration/             # Live API (skipped unless env vars present)
│  └─ fixtures/                # Sample JSON payloads/responses
├─ .github/workflows/
│  ├─ ci.yml                   # cs‑fix, phpstan, unit tests on 8.1–8.3
│  └─ release.yml              # on tag: run CI and notify Packagist
├─ composer.json
├─ README.md
├─ LICENSE
├─ phpunit.xml.dist
├─ phpstan.neon.dist
├─ .gitattributes              # export‑ignore tests, .github
└─ .gitignore
```

---

## HTTP Layer

**Default:** `symfony/http-client` with sensible defaults:
- `timeout`, `max_duration`, `headers` (JSON), `base_uri` (configurable), `retry_failed` policy enabled for idempotent verbs (GET/HEAD/PUT) with exponential backoff.
- On 4xx/5xx, `getContent()` throws specialized exceptions; we catch & map to SDK exceptions.

**Pluggable Transport (PSR‑18):**
- `Client` accepts an optional PSR‑18 `Psr\Http\Client\ClientInterface` + PSR‑17 factories. If provided, SDK uses that. Otherwise it uses Symfony by default.
- Provide a tiny `Psr18Adapter` to normalize error handling (convert HTTP error responses to `ApiException`, network problems to `MailrifyException`).

**Auth & Headers:**
- Always add `Authorization: Bearer <token>`.
- Default headers: `Content-Type: application/json`, `Accept: application/json`, `User-Agent: mailrify-php/<version>`.
- Optional idempotency header for unsafe endpoints if backend supports it: `Idempotency-Key` (UUID v4), opt‑in.

---

## Public API Shape (Resources & Methods)

Mirror OpenAPI paths with **resource classes** and **human‑friendly verbs**. Examples (illustrative; align with spec):

```php
final class EmailsApi
{
    public function __construct(private readonly Client $client) {}

    /** @return Models\Email */
    public function send(array|Models\Email $payload): Models\Email {}

    /** @return Models\Email */
    public function get(string $emailId): Models\Email {}

    /** @return list<Models\Email> */
    public function list(array $filters = []): array {}

    public function cancel(string $emailId): void {}

    /** @param list<array|Models\Email> $batch */
    public function batch(array $batch): BatchResult {}
}
```

- **Inputs:** Accept idiomatic PHP arrays **or** strongly‑typed DTOs; normalize internally.
- **Outputs:** Return typed DTOs; never raw JSON strings.
- **Validation:** Validate required fields early; throw `ValidationException` before network call.

---

## DTOs (Models)

- Use **readonly** properties and named constructors: `Email::fromArray(array $data)`; `->toArray()` for requests.
- Represent fixed domains as **PHP 8.1 enums** (e.g., `EmailStatus`).
- Keep models thin (data + simple helpers) to avoid hidden network calls.

Example:

```php
final class Email
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $subject,
        public readonly EmailStatus $status,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            id: (string)$d['id'],
            subject: $d['subject'] ?? null,
            status: EmailStatus::from($d['status'] ?? 'queued'),
        );
    }
}
```

---

## Error Model

Define a clear hierarchy and **map transport errors and API errors**:

- `MailrifyException extends \RuntimeException`
- `ApiException extends MailrifyException` (has `status`, `code`, `requestId`, `responseBody`)
- `AuthException extends ApiException` (401)
- `RateLimitException extends ApiException` (429; include `retryAfter` if present)
- `ValidationException extends MailrifyException` (pre‑request input issues)

**Behavior:**
- Network/timeout → `MailrifyException` with previous exception.
- HTTP 4xx/5xx → parse body `{ code, message, details? }` → throw mapped `ApiException`.
- Include `X-Request-ID`/`x-request-id` if provided by API.

---

## Configuration

```php
final class Config
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = 'https://app.mailrify.com/api',
        public readonly float $timeout = 10.0,
        public readonly ?int $maxRetries = 3,
    ) {}
}
```

- `Client` requires `apiKey`. Read from `MAILRIFY_API_KEY` env var if not passed.
- Allow overriding `baseUrl` for staging.
- Retries apply to idempotent requests only by default.

---

## Testing Strategy

**Unit Tests** (PHPUnit or Pest):
- Mock HTTP: 
  - Symfony: `MockHttpClient` with predefined `ResponseInterface`.
  - PSR‑18 path: inject a dummy client returning canned PSR‑7 responses.
- Assert: correct method/path/query/body/headers; error mapping; validation pre‑checks.

**Integration Tests** (opt‑in):
- Skipped unless `MAILRIFY_API_KEY` (and optional `MAILRIFY_BASE_URL`) are set.
- Smoke flows:
  - Emails: send → get → list → cancel/schedule (if applicable).
  - Domains: create → verify → get → delete (use disposable/sandbox).
  - Campaigns: create → schedule → pause → resume → get.
- Clean up created resources.

---

## Composer & Distribution

**composer.json (skeleton):**

```json
{
  "name": "mailrify/mailrify-php",
  "description": "Official PHP SDK for Mailrify (transactional & marketing email)",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": ">=8.1",
    "symfony/http-client": "^6.4 || ^7.0",
    "psr/http-client": "^1.0",
    "psr/http-message": "^1.0",
    "psr/http-factory": "^1.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.6 || ^10.5",
    "phpstan/phpstan": "^1.11",
    "friendsofphp/php-cs-fixer": "^3.58",
    "pestphp/pest": "^2.0"
  },
  "autoload": {
    "psr-4": { "Mailrify\\Sdk\\": "src/" }
  },
  "autoload-dev": {
    "psr-4": { "Mailrify\\Sdk\\Tests\\": "tests/" }
  },
  "scripts": {
    "test": "phpunit",
    "analyse": "phpstan analyse --memory-limit=1G",
    "format": "php-cs-fixer fix --dry-run",
    "fix": "php-cs-fixer fix"
  },
  "suggest": {
    "guzzlehttp/guzzle": "Use Guzzle as PSR-18 client instead of Symfony, if preferred"
  }
}
```

**Packagist:**
- Public GitHub repo with `composer.json`.
- Submit to Packagist; enable GitHub hook for auto‑updates on tag.
- Use SemVer tags (`v1.0.0`, `v1.1.0`, …).

---

## CI/CD (GitHub Actions)

**ci.yml** (outline):
- Trigger on PR and push.
- Matrix: PHP 8.1, 8.2, 8.3.
- Steps: checkout → setup‑php (with extensions) → `composer install` → cs‑check → phpstan → unit tests.
- (Optional) integration job runs only if secrets present and branch is `main`.

**release.yml** (on tag):
- Repeat CI.
- Notify Packagist via webhook or API.
- Create GitHub Release from tag and CHANGELOG.

---

## Documentation

**README.md** should include:
- What is Mailrify? Requirements (PHP 8.1+). Install via Composer.
- Quickstart:

```php
use Mailrify\Sdk\Client;

$client = Client::create([ 'apiKey' => getenv('MAILRIFY_API_KEY') ]);

$email = $client->emails()->send([
    'from' => 'you@yourdomain.com',
    'to' => 'customer@example.com',
    'subject' => 'Welcome!',
    'text' => 'Hello from Mailrify PHP SDK',
]);
```

- Auth; configuring baseUrl, timeouts; enabling idempotency keys.
- API Coverage table: SDK methods → HTTP path.
- Error handling examples (catching `AuthException`, `RateLimitException`).
- How to run integration tests with env vars.

Inline PHPDoc on all public APIs; include `examples/` for common flows.

---

## Quality & Maintenance

- Strict types; PHPStan level max; no `mixed` in public API.
- Keep dependency surface minimal; monitor with Dependabot and `composer audit`.
- Provide ISSUE/PR templates and SECURITY.md (disclosure policy). Enable 2FA on GitHub & Packagist.

---

## Implementation Checklist (PHP)

- [ ] Define `composer.json` (PHP 8.1+, minimal deps).
- [ ] Implement `Config`, `Client`, HTTP factory (Symfony default) + optional PSR‑18 adapter.
- [ ] Implement **DomainsApi**: list/create/get/delete/verify.
- [ ] Implement **EmailsApi**: send/list/get/schedule/cancel/batch.
- [ ] Implement **CampaignsApi**: create/get/schedule/pause/resume.
- [ ] Implement DTOs with enums + `fromArray()`/`toArray()`.
- [ ] Implement error mapping (network vs. 4xx/5xx) with typed exceptions.
- [ ] Unit tests (mocked HTTP) for every public method; fixtures for payloads.
- [ ] Opt‑in integration tests (env‑guarded) with cleanup.
- [ ] CI: cs‑fix, phpstan, tests on PHP 8.1–8.3. Release workflow to Packagist.
- [ ] README with Quickstart, API coverage, examples. SECURITY.md.

---

## Example Signatures (Illustrative)

```php
namespace Mailrify\Sdk;

final class Client
{
    private function __construct(
        public readonly Config $config,
        private readonly Http\Contracts\HttpClient $http
    ) {}

    public static function create(array $opts): self
    {
        $config = new Config(
            apiKey: $opts['apiKey'] ?? getenv('MAILRIFY_API_KEY') ?? '',
            baseUrl: $opts['baseUrl'] ?? 'https://app.mailrify.com/api',
            timeout: (float)($opts['timeout'] ?? 10.0),
            maxRetries: $opts['maxRetries'] ?? 3,
        );
        if ($config->apiKey === '') {
            throw new Exceptions\AuthException('Missing API key');
        }
        $http = Http\HttpClientFactory::fromSymfony($config);
        return new self($config, $http);
    }

    public function emails(): Resources\EmailsApi { return new Resources\EmailsApi($this); }
    public function domains(): Resources\DomainsApi { return new Resources\DomainsApi($this); }
    public function campaigns(): Resources\CampaignsApi { return new Resources\CampaignsApi($this); }

    public function request(string $method, string $path, array $query = [], array $body = []): array
    {
        return $this->http->request($method, $this->config->baseUrl.$path, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->config->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'mailrify-php/1.x',
            ],
            'query'   => $query,
            'json'    => $body,
            'timeout' => $this->config->timeout,
            'retries' => $this->config->maxRetries,
        ]);
    }
}
```

> **Note:** The actual request/response shapes MUST match the OpenAPI 3.0 spec exactly.

**Outcome:** A production‑ready, secure, and well‑tested **PHP SDK** for Mailrify, mirroring the Node.js SDK’s principles, with minimal dependencies, excellent DX, robust error handling, and complete OpenAPI coverage.
