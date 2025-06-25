<?php

namespace Goodcat\I10n;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(fn() => $this->registerTranslatedRoute());
    }

    public function register(): void
    {
        Route::macro('lang', function (array $translations = []) {
            /** @var Route $this */
            $this->action['localized_path'] = $translations;

            return $this;
        });
    }

    protected function registerTranslatedRoute(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        /** @var Router $router */
        $router = App::make(Router::class);

        /** @var Route $route */
        foreach ($router->getRoutes() as $route) {
            /** @var array<string, string> $locales */
            $locales = $route->action['localized_path'] ?? [];

            foreach ($locales as $locale => $uri) {
                $localized = clone $route;

                $localized->action['locale'] = $locale;

                $localized->prefix($locale);

                if ($route->getName()) {
                    $localized->name(".$locale");
                };

                $router->addRoute($localized->methods, $uri, $localized->action);
            }
        }
    }
}