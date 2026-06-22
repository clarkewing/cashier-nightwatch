<?php

namespace ClarkeWing\CashierNightwatch;

use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use Laravel\Nightwatch\Core as NightwatchCore;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\State\RequestState;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Stripe\HttpClient\CurlClient;
use Throwable;

/**
 * A CurlClient that emits Nightwatch outgoingRequest() events for standard
 * (non-streaming) Stripe HTTP calls.
 *
 * Nightwatch only persists the method, URL, byte sizes and status code for an
 * outgoing request — never headers or bodies — so the URL is the only sensitive
 * surface. URLs pass through {@see UrlRedactor} before being recorded. Bodies
 * are reconstructed solely so Nightwatch can report accurate sizes; they are
 * never stored.
 */
class NightwatchCurlClient extends CurlClient
{
    /**
     * @param  NightwatchCore<RequestState|CommandState>  $nightwatch
     * @param  array<string, mixed>  $defaultOptions
     */
    public function __construct(
        protected readonly NightwatchCore $nightwatch,
        protected readonly UrlRedactor $redactor,
        array $defaultOptions = [],
    ) {
        parent::__construct($defaultOptions);
    }

    /**
     * @param  'delete'|'get'|'post'  $method
     * @param  string  $absUrl
     * @param  array<array-key, mixed>  $headers
     * @param  array<array-key, mixed>  $params
     * @param  bool  $hasFile
     * @param  'v1'|'v2'  $apiMode
     * @param  int|null  $maxNetworkRetries
     * @return array<array-key, mixed>
     */
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null) // @pest-ignore-type
    {
        if ($this->shouldSkip()) {
            return parent::request($method, $absUrl, $headers, $params, $hasFile, $apiMode, $maxNetworkRetries);
        }

        try {
            $start = $this->nightwatch->clock->microtime();
        } catch (Throwable $e) {
            $this->nightwatch->report($e, handled: true);

            return parent::request($method, $absUrl, $headers, $params, $hasFile, $apiMode, $maxNetworkRetries);
        }

        $response = parent::request($method, $absUrl, $headers, $params, $hasFile, $apiMode, $maxNetworkRetries);

        try {
            $end = $this->nightwatch->clock->microtime();

            [$body, $status] = $response;

            $this->nightwatch->outgoingRequest(
                $start,
                $end,
                $this->buildRequest($this->redactor, $method, $absUrl, $params, $hasFile),
                $this->buildResponse(is_int($status) ? $status : 0, is_string($body) ? $body : ''),
            );
        } catch (Throwable $e) {
            $this->nightwatch->report($e, handled: true);
        }

        return $response;
    }

    protected function shouldSkip(): bool
    {
        return (bool) data_get($this->nightwatch->config, 'filtering.ignore_outgoing_requests', false)
            || $this->nightwatch->paused();
    }

    /**
     * Build the request snapshot. Only the method and (redacted) URL survive
     * into Nightwatch; the body exists solely to report an accurate size.
     *
     * @param  array<array-key, mixed>|string|null  $params
     */
    protected function buildRequest(UrlRedactor $redactor, string $method, string $absUrl, array|string|null $params, bool $hasFile): RequestInterface
    {
        $method = strtoupper($method);
        $url = $absUrl;
        $body = '';

        if ($method === 'GET') {
            if (is_array($params) && $params !== []) {
                $url .= (str_contains($absUrl, '?') ? '&' : '?').http_build_query($params);
            }
        } elseif (! $hasFile && is_array($params)) {
            $body = http_build_query($params);
        } elseif (! $hasFile && is_string($params)) {
            $body = $params;
        }

        return new Psr7Request($method, $redactor->redact($url), [], Psr7Utils::streamFor($body));
    }

    protected function buildResponse(int $status, string $body): ResponseInterface
    {
        return new Psr7Response($status, [], Psr7Utils::streamFor($body));
    }
}
