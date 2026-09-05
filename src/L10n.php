<?php

namespace Goodcat\L10n;

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
        $router = app(Router::class);

        $collection = $router->getRoutes();

        if (RouteStrategy::from(config('l10n.route_strategy'))->isPrefix()) {
            $router->setRoutes($this->prefixedCollection($collection));

            return;
        }

        foreach ($collection->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */
            if ($this->isPending($route)) {
                $this->addTranslations($collection, $route);
            }
        }
    }

    protected function prefixedCollection(RouteCollectionInterface $collection): RouteCollection
    {
        $routes = new RouteCollection;

        foreach ($collection->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */
            $pending = $this->isPending($route);

            if ($pending) {
                $this->prefixCanonicalRoute($route);
            }

            $routes->add($route);

            if ($pending) {
                $this->addTranslations($routes, $route);
            }
        }

        return $routes;
    }

    /**
     * @param  Route&LocalizedRoute  $route
     */
    protected function isPending(Route $route): bool
    {
        return ! $route->getAction('canonical')
            && ! $route->getAction('key')
            && (bool) $route->getAction('lang');
    }

    /**
     * @param  Route&LocalizedRoute  $route
     */
    protected function addTranslations(RouteCollectionInterface $routes, Route $route): void
    {
        $route->action['key'] = $route->getKey();

        foreach ($route->makeTranslations() as $localizedRoute) {
            $routes->add($localizedRoute);
        }
    }

    /**
     * @param  Route&LocalizedRoute  $route
     */
    protected function prefixCanonicalRoute(Route $route): void
    {
        $route->action['source_uri'] = $route->uri();

        $bindingFields = $route->bindingFields();

        $route->prefix(app()->getFallbackLocale())->setBindingFields($bindingFields);

        $route->action['locale'] = app()->getFallbackLocale();
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
