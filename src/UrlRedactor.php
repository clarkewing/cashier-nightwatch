<?php

namespace ClarkeWing\CashierNightwatch;

/**
 * Redacts the sensitive parts of a Stripe API URL before it is handed to
 * Nightwatch.
 *
 * Nightwatch only ever stores the URL for an outgoing request, so the URL is
 * the entire redaction surface. Query-string values are redacted by default
 * because Stripe list/search calls carry filters (email, customer, ...) there.
 */
readonly class UrlRedactor
{
    /**
     * @param  string  $queryMode  One of "keys", "drop" or "keep".
     */
    public function __construct(
        protected string $queryMode = 'keys',
        protected bool $maskPathIds = false,
        protected string $placeholder = 'REDACTED',
    ) {
        //
    }

    public function redact(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            // Unparseable: fail closed by dropping anything after the path.
            return explode('?', $url, 2)[0];
        }

        if ($this->maskPathIds && isset($parts['path'])) {
            $parts['path'] = $this->maskResourceIds($parts['path']);
        }

        if (isset($parts['query'])) {
            $parts['query'] = $this->redactQuery($parts['query']);
        }

        return $this->rebuild($parts);
    }

    protected function redactQuery(string $query): string
    {
        return match ($this->queryMode) {
            'keep' => $query,
            'drop' => '',
            default => implode('&', array_map(
                fn (string $pair): string => $pair === ''
                    ? $pair
                    : explode('=', $pair, 2)[0].'='.$this->placeholder,
                explode('&', $query),
            )),
        };
    }

    protected function maskResourceIds(string $path): string
    {
        return preg_replace('/\b([a-z]{2,})_[A-Za-z0-9]{6,}\b/', '$1_***', $path) ?? $path;
    }

    /**
     * @param  array<string, int|string>  $parts
     */
    protected function rebuild(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
