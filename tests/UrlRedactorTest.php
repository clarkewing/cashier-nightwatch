<?php

use ClarkeWing\CashierNightwatch\UrlRedactor;

it('redacts query values but keeps the keys by default', function () {
    expect((new UrlRedactor)->redact('https://api.stripe.com/v1/customers?email=jane@example.com&limit=3'))
        ->toBe('https://api.stripe.com/v1/customers?email=REDACTED&limit=REDACTED');
});

it('never leaks PII held in the query string', function () {
    expect((new UrlRedactor)->redact('https://api.stripe.com/v1/customers/search?query=email:jane@example.com'))
        ->not->toContain('jane@example.com');
});

it('leaves a query-less URL untouched', function () {
    expect((new UrlRedactor)->redact('https://api.stripe.com/v1/payment_intents'))
        ->toBe('https://api.stripe.com/v1/payment_intents');
});

it('drops the query string entirely in drop mode', function () {
    expect((new UrlRedactor(queryMode: 'drop'))->redact('https://api.stripe.com/v1/customers?email=jane@example.com'))
        ->toBe('https://api.stripe.com/v1/customers');
});

it('leaves the query untouched in keep mode', function () {
    expect((new UrlRedactor(queryMode: 'keep'))->redact('https://api.stripe.com/v1/customers?limit=3'))
        ->toBe('https://api.stripe.com/v1/customers?limit=3');
});

it('keeps resource IDs in the path by default', function () {
    expect((new UrlRedactor)->redact('https://api.stripe.com/v1/customers/cus_ABC123456'))
        ->toBe('https://api.stripe.com/v1/customers/cus_ABC123456');
});

it('masks resource IDs in the path when enabled', function () {
    expect((new UrlRedactor(maskPathIds: true))->redact('https://api.stripe.com/v1/customers/cus_ABC123456/sources/card_XYZ789012'))
        ->toBe('https://api.stripe.com/v1/customers/cus_***/sources/card_***');
});

it('fails closed by dropping the query when the URL cannot be parsed', function () {
    // A non-numeric port makes parse_url() return false.
    expect((new UrlRedactor)->redact('https://api.stripe.com:port/v1/customers?email=jane@example.com'))
        ->not->toContain('jane@example.com');
});
