# Cashier Nightwatch

Surface Stripe API requests — those made by [Laravel Cashier](https://laravel.com/docs/billing) and the Stripe PHP SDK — on your [Laravel Nightwatch](https://nightwatch.laravel.com) timelines, with the request URL redacted by default.

Stripe's PHP SDK uses its own cURL client, so its API calls bypass Nightwatch's Guzzle instrumentation and never show up on your timelines. This package swaps in a Nightwatch-aware cURL client so they do.

## Installation

```bash
composer require clarkewing/cashier-nightwatch
```

The service provider is auto-discovered. To customise redaction, publish the config:

```bash
php artisan vendor:publish --tag=cashier-nightwatch-config
```

## What is recorded — and redacted

Nightwatch stores only the **method, URL, byte sizes and status code** for an outgoing request — never request/response **headers or bodies**. Consequently:

- Your Stripe **secret key** (the `Authorization` header) is never captured.
- Request/response **bodies** (card data, customer PII on writes) are never captured — only their byte size.
- The **URL is the only sensitive surface**, because Stripe `list`/`search` calls put filters such as `email` or `customer` in the query string.

By default the package **redacts query-string values while keeping the keys**:

```
GET https://api.stripe.com/v1/customers?email=REDACTED&limit=REDACTED
```

### Configuration

| Key | Default | Description                                                                                                   |
| --- | --- |---------------------------------------------------------------------------------------------------------------|
| `enabled` | `true` | Master switch. No-ops if Nightwatch is not installed.                                                         |
| `redact_query` | `keys` | `keys` (redact values, keep names)<br>`drop` (remove the query string)<br>`keep` (leave as-is — may log PII). |
| `mask_path_ids` | `false` | Mask Stripe IDs in the path, e.g. `/v1/customers/cus_***`.                                                    |

Each maps to an env var: `CASHIER_NIGHTWATCH_ENABLED`, `CASHIER_NIGHTWATCH_REDACT_QUERY`, `CASHIER_NIGHTWATCH_MASK_PATH_IDS`.

For full control you can also use [Nightwatch's own hook](https://nightwatch.laravel.com/docs/outgoing-requests#redaction) in a service provider (the record's `url` is mutable):

```php
use Laravel\Nightwatch\Facades\Nightwatch;

Nightwatch::redactOutgoingRequests(function ($record) {
    $record->url = '...';
});
```

## Compatibility

This integrates at the Stripe SDK level (`Stripe\ApiRequestor::setHttpClient`) and relies on a few Laravel Nightwatch internals (`Core::$clock`, `Core::$config`, `outgoingRequest()`, `paused()`). It targets Nightwatch `1.x` and may need updating across a Nightwatch major version.

Streaming Stripe responses (`requestStream`) are not instrumented.

## Testing

```bash
composer install
composer test
```

## License

MIT — see [LICENSE](LICENSE).
