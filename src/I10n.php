<?php

namespace Goodcat\I10n;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;

class I10n
{
    public static function route(string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        if (!$locale) $locale = App::getLocale();

        $name .= ".$locale";

        return app('url')->route($name, $parameters, $absolute);
    }

    public static function detectBrowserLocale(Request $request): ?string
    {
        $locales = array_intersect($request->getLanguages(), config('app.locales', []));

        return array_pop($locales);
    }

    public static function registerTranslatedRoutes(): void
    {
        if (App::routesAreCached()) {
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