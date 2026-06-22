<?php

use ClarkeWing\CashierNightwatch\NightwatchCurlClient;
use ClarkeWing\CashierNightwatch\UrlRedactor;

/**
 * The build* methods are protected, so we instantiate the client without its
 * constructor (which needs a Nightwatch Core) and reach in with spatie/invade.
 */
function nightwatchClient(): NightwatchCurlClient
{
    return (new ReflectionClass(NightwatchCurlClient::class))->newInstanceWithoutConstructor();
}

it('redacts GET query parameters in the recorded request URL', function () {
    $request = invade(nightwatchClient())->buildRequest(
        new UrlRedactor,
        'GET',
        'https://api.stripe.com/v1/customers',
        ['email' => 'jane@example.com', 'limit' => 3],
        false,
    );

    expect((string) $request->getUri())
        ->toContain('email=REDACTED')
        ->not->toContain('jane@example.com');
});

it('keeps POST parameters out of the URL (the body is sized, never stored)', function () {
    $request = invade(nightwatchClient())->buildRequest(
        new UrlRedactor,
        'POST',
        'https://api.stripe.com/v1/customers',
        ['email' => 'jane@example.com'],
        false,
    );

    expect((string) $request->getUri())->toBe('https://api.stripe.com/v1/customers')
        ->and($request->getBody()->getSize())->toBeGreaterThan(0);
});

it('builds a response carrying only status and size', function () {
    $response = invade(nightwatchClient())->buildResponse(200, '{"id":"cus_123"}');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getBody()->getSize())->toBe(16);
});
