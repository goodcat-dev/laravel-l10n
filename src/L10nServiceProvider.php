<?php

namespace Goodcat\L10n;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn () => L10n::registerTranslatedRoutes());
    }

    public function register(): void
    {
        $this->app->singleton(L10n::class, fn () => new L10n());

        Route::macro('lang', function (array $translations = []) {
            /** @var Route $this */
            $this->action['localized_path'] = $translations;

            return $this;
        });
    }
}
