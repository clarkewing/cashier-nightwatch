<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch. When disabled — or when Laravel Nightwatch is not
    | installed — the Stripe HTTP client is left untouched and nothing is
    | recorded.
    |
    */

    'enabled' => env('CASHIER_NIGHTWATCH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Query string redaction
    |--------------------------------------------------------------------------
    |
    | Nightwatch stores the request URL for outgoing requests (and only the URL
    | — never headers or bodies). Stripe "list" and "search" calls put filters
    | such as "email" or "customer" in the query string, so values are redacted
    | by default.
    |
    | Supported:
    | - "keys" — keep parameter names, redact their values (default)
    | - "drop" — remove the query string entirely
    | - "keep" — leave the query string untouched (may log PII; not recommended)
    |
    */

    'redact_query' => env('CASHIER_NIGHTWATCH_REDACT_QUERY', 'keys'),

    /*
    |--------------------------------------------------------------------------
    | Mask resource IDs in the path
    |--------------------------------------------------------------------------
    |
    | Optionally rewrite Stripe resource IDs in the URL path, e.g.
    | "/v1/customers/cus_123" => "/v1/customers/cus_***". Off by default: the
    | IDs are opaque and keeping them aids grouping and debugging.
    |
    */

    'mask_path_ids' => env('CASHIER_NIGHTWATCH_MASK_PATH_IDS', false),

];
