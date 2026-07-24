<?php

namespace Goodcat\L10n;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\LocaleResolver;
use Goodcat\L10n\Resolvers\SessionLocale;
use Goodcat\L10n\Resolvers\UserLocale;
use Goodcat\L10n\Routing\RouteStrategy;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\Router;

class L10n
{
    /** @var list<LocaleResolver> */
    public static array $preferredLocaleResolvers;

    public function registerLocalizedRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        $collection = app(Router::class)->getRoutes();

        $strategy = RouteStrategy::from(config('l10n.route_strategy'));

        foreach ($collection->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */
            if ($route->getAction('canonical') || $route->getAction('key') || ! $route->getAction('lang')) {
                continue;
            }

            if ($strategy->isPrefix()) {
                $this->prefixAndReindexCanonicalRoute($collection, $route);
            }

            $route->action['key'] = $route->getKey();

            foreach ($route->makeTranslations() as $localizedRoute) {
                $collection->add($localizedRoute);
            }
        }
    }

    /**
     * @param  Route&LocalizedRoute  $route
     */
    protected function prefixAndReindexCanonicalRoute(RouteCollectionInterface $collection, Route $route): void
    {
        $key = $route->getKey();

        $domainAndUri = $route->getDomain().$route->uri();

        $route->action['source_uri'] = $route->uri();

        $bindingFields = $route->bindingFields();

        $route->prefix(app()->getFallbackLocale())->setBindingFields($bindingFields);

        $route->action['locale'] = app()->getFallbackLocale();

        $reindex = Closure::bind(function (Route $route, string $key, string $domainAndUri): void {
            foreach ($route->methods() as $method) {
                unset($this->routes[$method][$domainAndUri]);

                $this->routes[$method][$route->getDomain().$route->uri()] = $route;
            }

            unset($this->allRoutes[$key]);

            $this->allRoutes[$route->getKey()] = $route;
        }, $collection, RouteCollection::class);

        $reindex($route, $key, $domainAndUri);
    }

    public function is(string ...$patterns): bool
    {
        /** @var (LocalizedRoute&Route)|null $route */
        $route = app(Router::class)->current();

        return $route && $route->canonical()->named(...$patterns);
    }

    /**
     * @return list<LocaleResolver>
     */
    public static function getPreferredLocaleResolvers(): array
    {
        if (! isset(self::$preferredLocaleResolvers)) {
            self::$preferredLocaleResolvers = [
                new SessionLocale,
                new UserLocale,
                new BrowserLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }
}
