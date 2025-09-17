<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class L10n
{
    public static bool $hideDefaultLocale = true;

    public static string $localizedViewsPath = '';

    public static array $preferredLocaleResolvers;

    public function registerLocalizedRoutes(): void
    {
        if (\app()->routesAreCached()) {
            return;
        }

        $routeCollection = app(Router::class)->getRoutes();

        $routeCollection->refreshNameLookups();

        /** @var Route $route */
        foreach ($routeCollection as $route) {
            /** @var RouteTranslations $translations */
            $translations = $route->lang();

            if ($translations->isEmpty()) {
                continue;
            }

            foreach ($translations->genericLocales() as $locale) {
                $key = "routes.{$route->uri()}";

                if (trans()->hasForLocale($key, $locale)) {
                    $translations->addTranslations([
                        $locale => trans($key, locale: $locale),
                    ]);
                }
            }

            $hasLangParameter = in_array('lang', $route->parameterNames());

            if (! $hasLangParameter && $translations->hasGeneric()) {
                throw new \LogicException("Localized route \"$route->uri\" requires {lang} parameter.");
            }

            $translations->addTranslations([app()->getFallbackLocale()]);

            $this->registerAliasRoutes($route);

            if (self::$hideDefaultLocale && $hasLangParameter) {
                $this->registerFallbackRoute($route);
            }

            if ($hasLangParameter) {
                $route->whereIn('lang', $translations->genericLocales());
            }
        }
    }

    protected function registerAliasRoutes(Route $route)
    {
        /** @var RouteTranslations $translations */
        $translations = $route->lang();

        foreach (array_filter($translations->all()) as $locale => $uri) {
            $action = $route->action;

            if ($route->getName()) {
                $action['as'] = "{$route->getName()}#$locale";
            }

            $action['prefix'] = str_replace('{lang}', $locale, $route->getPrefix());
            $action['locale'] = $locale;

            app(Router::class)->addRoute($route->methods, $uri, $action);
        }
    }

    protected function registerFallbackRoute(Route $route): void
    {
        $action = $route->action;

        $fallback = app()->getFallbackLocale();

        if ($route->getName()) {
            $action['as'] = "{$route->getName()}#$fallback";
        }

        $action['prefix'] = '';
        $action['locale'] = $fallback;

        $uri = str_replace('{lang}/', '', $route->uri);

        app(Router::class)->addRoute($route->methods, $uri, $action);

        /** @var RouteTranslations $translations */
        $translations = $route->lang();

        $translations->addTranslations([$fallback => $uri]);
    }

    public static function getPreferredLocaleResolvers(): array
    {
        if (! isset(self::$preferredLocaleResolvers)) {
            self::$preferredLocaleResolvers = [
                new UserPreferredLocale,
                new BrowserLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }
}
