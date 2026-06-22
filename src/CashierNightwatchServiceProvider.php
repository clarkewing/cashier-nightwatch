<?php

namespace ClarkeWing\CashierNightwatch;

use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Core as NightwatchCore;
use Stripe\ApiRequestor as StripeApiRequestor;

class CashierNightwatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cashier-nightwatch.php', 'cashier-nightwatch');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/cashier-nightwatch.php' => config_path('cashier-nightwatch.php'),
        ], 'cashier-nightwatch-config');

        if (! $this->shouldInstrument()) {
            return;
        }

        $redactQuery = config('cashier-nightwatch.redact_query', 'keys');

        StripeApiRequestor::setHttpClient(new NightwatchCurlClient(
            $this->app->make(NightwatchCore::class),
            new UrlRedactor(
                queryMode: is_string($redactQuery) ? $redactQuery : 'keys',
                maskPathIds: (bool) config('cashier-nightwatch.mask_path_ids', false),
            ),
        ));
    }

    protected function shouldInstrument(): bool
    {
        return (bool) config('cashier-nightwatch.enabled', true)
            && class_exists(NightwatchCore::class)
            && class_exists(StripeApiRequestor::class)
            && $this->app->bound(NightwatchCore::class);
    }
}
