<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
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

        $collection = app(Router::class)->getRoutes();

        $newCollection = new RouteCollection();

        /** @var Route $route */
        foreach ($collection as $route) {
            /** @var RouteTranslations $translations */
            $translations = $route->lang();

            if ($translations->isEmpty()) {
                $newCollection->add($route);

                continue;
            }

            foreach ($translations->genericLocales() as $locale) {
                $key = 'routes.' . ltrim($route->uri(), "{$route->getPrefix()}/");

                if (trans()->hasForLocale($key, $locale)) {
                    $translations->addTranslations([
                        $locale => trans($key, locale: $locale),
                    ]);
                }
            }

            $hasLangParameter = in_array('lang', $route->parameterNames());

            if (! $hasLangParameter && $translations->hasGeneric()) {
                $route->prefix('{lang}');

                $hasLangParameter = true;
            }

            $newCollection->add($route);

            $translations->addTranslations([app()->getFallbackLocale()]);

            $this->registerAliasRoutes($route, $newCollection);

            if (self::$hideDefaultLocale && $hasLangParameter) {
                $this->registerFallbackRoute($route, $newCollection);
            }

            if ($hasLangParameter) {
                $route->whereIn('lang', $translations->genericLocales());
            }

            app(Router::class)->setRoutes($newCollection);
        }
    }

    protected function registerAliasRoutes(Route $route, RouteCollection $collection): void
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

            $collection->add(new Route($route->methods, $uri, $action));
        }
    }

    protected function registerFallbackRoute(Route $route, RouteCollection $collection): void
    {
        $action = $route->action;

        $fallback = app()->getFallbackLocale();

        if ($route->getName()) {
            $action['as'] = "{$route->getName()}#$fallback";
        }

        $action['prefix'] = '';
        $action['locale'] = $fallback;

        $uri = str_replace('{lang}/', '', $route->uri);

        $collection->add(new Route($route->methods, $uri, $action));

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
