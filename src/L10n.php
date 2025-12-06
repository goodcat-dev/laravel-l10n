<?php

namespace Goodcat\L10n;

use Closure;
use Goodcat\L10n\Mixin\LocalizedRoute;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\PreferredLocaleResolver;
use Goodcat\L10n\Resolvers\UserPreferredLocale;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
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

        /** @var RouteCollection $collection */
        $collection = app(Router::class)->getRoutes();

        foreach ($collection->getRoutes() as $route) {
            /** @var Route|LocalizedRoute $route */

            /** @var RouteTranslations $translations */
            $translations = $route->lang();

            if ($translations->isEmpty()) {
                continue;
            }

            $this->forgetRoute($route, $collection);

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
        if (! isset(self::$preferredLocaleResolvers)) {
            self::$preferredLocaleResolvers = [
                new UserPreferredLocale,
                new BrowserLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }

    public function forgetRoute(Route $route, RouteCollection $collection): void
    {
        $forget = Closure::bind(function (Route $route): void {
            $methods = $route->methods();
            $domainAndUri = $route->getDomain().$route->uri();

            foreach ($methods as $method) {
                unset($this->routes[$method][$domainAndUri]);
            }

            unset($this->allRoutes[implode('|', $methods).$domainAndUri]);

            if ($name = $route->getName()) {
                unset($this->nameList[$name]);
            }

            unset($this->actionList[$route->getActionName()]);
        }, $collection, RouteCollection::class);

        $forget($route);
    }
}
