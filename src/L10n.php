<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Contracts\LocalizedRoute;
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

        $collection = app(Router::class)->getRoutes();

        foreach ($collection->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */

            foreach ($route->makeTranslations() as $localizedRoute) {
                $collection->add($localizedRoute);
            }

            $collection->add($route);
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
