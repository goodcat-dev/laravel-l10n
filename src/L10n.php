<?php

namespace Goodcat\L10n;

use Closure;
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

        /** @var Route $route */
        foreach ($collection as $route) {
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
                app()->getFallbackLocale() => self::$hideDefaultLocale
                    ? str_replace($route->getPrefix(), '', $route->uri())
                    : null
            ]);

            $this->registerGenericRoute($route, $collection);

            $this->registerAliasRoutes($route, $collection);
        }
    }

    protected function registerGenericRoute(Route $route, RouteCollection $collection): void
    {
        /** @var RouteTranslations $translations */
        $translations = $route->lang();

        if (! $translations->hasGeneric()) {
            return;
        }

        $genericRoute = clone $route;

        $hasLangParameter = in_array('lang', $route->parameterNames());

        if (! $hasLangParameter) {
            $genericRoute->prefix('{lang}');
        }

        $genericRoute->whereIn('lang', $translations->genericLocales());

        $collection->add($genericRoute);
    }

    protected function registerAliasRoutes(Route $route, RouteCollection $collection): void
    {
        /** @var RouteTranslations $translations */
        $translations = $route->lang();

        foreach ($translations->aliasLocales() as $locale) {
            $action = $route->action;

            $action['locale'] = $locale;

            if ($name = $route->getName()) {
                $action['as'] = "$name#$locale";
            }

            $uri = str_replace('{lang}', $locale, $translations->all()[$locale]);

            if ($prefix = $route->getPrefix()) {
                $action['prefix'] = str_replace('{lang}', $locale, $prefix);
            }

            if (
                self::$hideDefaultLocale
                && app()->isFallbackLocale($locale)
            ) {
                $uri = preg_replace('#/+#', '/', str_replace($locale, '', $uri));

                $action['prefix'] = preg_replace('#/+#', '/', str_replace($locale, '', $action['prefix'] ?? ''));
            }

            $collection->add(new Route($route->methods(), $uri, $action));
        }
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
