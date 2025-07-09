<?php

namespace Goodcat\L10n;

use Goodcat\L10n\LocaleResolvers\BrowserLocale;
use Goodcat\L10n\LocaleResolvers\RouteLocale;
use Goodcat\L10n\LocaleResolvers\SessionLocale;
use Goodcat\L10n\LocaleResolvers\LocaleResolverInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route as RouteFacades;

class L10n
{
    /** @var LocaleResolverInterface[]  */
    public static array $localeResolvers = [];

    /**
     * @return LocaleResolverInterface[]
     */
    public static function getLocaleResolvers(): array
    {
        if (!static::$localeResolvers) {
            return [new SessionLocale, new RouteLocale, new BrowserLocale];
        }

        return static::$localeResolvers;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        if (
            $locale !== App::getFallbackLocale()
            && RouteFacades::has("$name.$locale")
        ) {
            $name .= ".$locale";
        }

        return app('url')->route($name, $parameters, $absolute);
    }

    public static function toRoute(string $route, mixed $parameters = [], int $status = 302, array $headers = [], ?string $locale = null): RedirectResponse
    {
        $locale ??= App::getLocale();

        if (
            $locale !== App::getFallbackLocale()
            && RouteFacades::has("$route.$locale")
        ) {
            $route .= ".$locale";
        }

        return redirect()->route($route, $parameters, $status, $headers);
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