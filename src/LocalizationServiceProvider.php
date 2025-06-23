<?php

namespace Goodcat\I10n;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
    public function register(): void
    {
        Route::macro('lang', function (array $translations = []) {
            /** @var Route $this */
            $this->action['localized_path'] = $translations;

            return $this;
        });
    }
}