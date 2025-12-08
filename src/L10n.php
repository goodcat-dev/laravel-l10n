<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Contracts\LocalizedRouter;
use Goodcat\L10n\Resolvers\BrowserPreferredLocale;
use Goodcat\L10n\Resolvers\PreferredLocaleResolver;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
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
            $translations = $route->lang()->fillMissing($route);

            if ($translations->isEmpty()) {
                continue;
            }

            $router->forget($route);

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
                new BrowserPreferredLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }
}
