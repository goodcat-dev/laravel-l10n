<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn () => L10n::registerLocalizedRoute());
    }

    public function register(): void
    {
        $this->app->singleton(L10n::class, fn () => new L10n);

        Route::macro('lang', function (?array $translations = null): Route|RouteTranslations {
            /** @var Route $this */

            $lang = $this->action['lang'] ?? [];

            if (is_array($lang)) {
                $lang = new RouteTranslations($lang);
            }

            $lang->addTranslations($translations ?? []);

            $this->action['lang'] = $lang;

            return is_null($translations) ? $lang : $this;
        });
    }
}
