<?php

namespace Goodcat\I10n;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacades;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $routeCollection = RouteFacades::getRoutes();

            /** @var Route $route */
            foreach ($routeCollection as $route) {
                /** @var array<string, string> $locales */
                $locales = @$route->action['localized_path'];

                if (!$locales) continue;

                foreach ($locales as $locale => $path) {
                    $localizedRoute = clone $route;

                    $localizedRoute->uri = $path;

                    $localizedRoute->prefix($route->getPrefix());

                    if ($route->getName()) {
                        $localizedRoute->name(".$locale");
                    };

                    $routeCollection->add($localizedRoute);
                }
            }
        });
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