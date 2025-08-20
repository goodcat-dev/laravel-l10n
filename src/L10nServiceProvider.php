<?php

namespace Goodcat\L10n;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class L10nServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->singleton(L10n::class, fn () => new L10n);

        Route::macro('lang', function (array $translations = []) {
            /** @var Route $this */

            $this->action['lang'] = array_merge($this->getAction('lang') ?? [], $translations);

            $locales = [];

            foreach ($this->getAction('lang') ?? [] as $locale => $uri) {
                is_int($locale)
                    ? $locales[$uri] = null
                    : $locales[$locale] = $uri;
            }

            unset($this->action['lang']);

            if ($locales) {
                $locales += ['en' => null];

                $this->action['lang'] = $locales + ['en' => null];

                $this->whereIn('lang', array_keys($locales));
            }

            return $this;
        });
    }
}
