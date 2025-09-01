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

    public static function registerLocalizedRoute(): void
    {
        if (\app()->routesAreCached()) {
            return;
        }

        /** @var Router $router */
        $router = \app(Router::class);

        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        foreach ($router->getRoutes() as $route) {
            /** @var Route $route */

            /** @var RouteTranslations $translations */
            $translations = $route->lang();

            if ($translations->isEmpty()) {
                continue;
            }

            $fallback = \app()->getFallbackLocale();

            $translations->addTranslations([$fallback]);

            $hasLangParameter = in_array('lang', $route->parameterNames());

            if (!$hasLangParameter && $translations->hasGeneric()) {
                throw new \LogicException("Localized route \"$route->uri\" requires {lang} parameter.");
            }

            foreach (array_filter($translations->all()) as $locale => $uri) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] = "{$route->getName()}#$locale";
                }

                $action['prefix'] = str_replace('{lang}', $locale, $route->getPrefix());
                $action['locale'] = $locale;

                $router->addRoute($route->methods, $uri, $action);
            }

            if (self::$hideDefaultLocale && $hasLangParameter) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] = "{$route->getName()}#$fallback";
                }

                $action['prefix'] = '';
                $action['locale'] = $fallback;

                $uri = str_replace("{lang}/", '', $route->uri);

                $router->addRoute($route->methods, $uri, $action);

                $translations->addTranslations([$fallback => $uri]);
            }

            if ($hasLangParameter) {
                $route->whereIn('lang', $translations->genericLocales());
            }
        }
    }

    public static function getPreferredLocaleResolvers(): array
    {
        if (!isset(self::$preferredLocaleResolvers)) {
            self::$preferredLocaleResolvers = [
                new UserPreferredLocale,
                new BrowserLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }
}
