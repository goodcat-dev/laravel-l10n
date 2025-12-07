<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Contracts\LocalizedRouter;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\PreferredLocaleResolver;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class L10n
{
    /** @var PreferredLocaleResolver[] */
    public static array $preferredLocaleResolvers;

    public function registerLocalizedRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        /** @var Router&LocalizedRouter $router */
        $router = app(Router::class);

        $collection = $router->getRoutes();

        /** @var Route&LocalizedRoute $route */
        foreach ($collection->getRoutes() as $route) {
            /** @var RouteTranslations $translations */
            $translations = $route->lang();

            if ($translations->isEmpty()) {
                continue;
            }

            $router->forget($route);

            $key = 'routes.' . ltrim($route->uri(), "{$route->getPrefix()}/");

            foreach ($translations->genericLocales() as $locale) {
                if (trans()->hasForLocale($key, $locale)) {
                    $translations->addTranslations([
                        $locale => trans($key, locale: $locale),
                    ]);
                }
            }

            $translations->addTranslations([
                app()->getFallbackLocale() => config('l10n.hide_default_locale')
                    ? str_replace($route->getPrefix(), '', $route->uri())
                    : null
            ]);

            foreach ($route->makeTranslations() as $localizedRoute) {
                $collection->add($localizedRoute);
            }
        }
    }

    /**
     * @return PreferredLocaleResolver[]
     */
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
