<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Contracts\LocalizedRouter;
use Goodcat\L10n\Resolvers\BrowserLocale;
use Goodcat\L10n\Resolvers\LocaleResolver;
use Goodcat\L10n\Resolvers\SessionLocale;
use Goodcat\L10n\Resolvers\UserLocale;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class L10n
{
    /** @var LocaleResolver[] */
    public static array $preferredLocaleResolvers;

    /** @var array<string, Route>  */
    protected array $canonicalRoutes;

    public function registerLocalizedRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        $collection = app(Router::class)->getRoutes();

        foreach ($collection->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */

            if ($route->getAction('canonical')) {
                continue;
            }

            foreach ($route->makeTranslations() as $localizedRoute) {
                $collection->add($localizedRoute);
            }
        }
    }

    public function is(string ...$patterns): bool
    {
        /** @var LocalizedRouter&Router $router */
        $router = app(Router::class);

        $route = $router->current();

        if ($canonical = $route?->getAction('canonical')) {
            $route = $this->getByKey($canonical);
        }

        return $route && $route->named(...$patterns);
    }

    public function getByKey(string $key): ?Route
    {
        if (! isset($this->canonicalRoutes)) {
            $this->refreshCanonicalLookups();
        }

        return $this->canonicalRoutes[$key] ?? null;
    }

    public function refreshCanonicalLookups(): void
    {
        $this->canonicalRoutes = [];

        foreach (app(Router::class)->getRoutes()->getRoutes() as $route) {
            if (! $route->getAction('canonical')) {
                $this->canonicalRoutes[$route->getKey()] = $route;
            }
        }
    }

    /**
     * @return LocaleResolver[]
     */
    public static function getPreferredLocaleResolvers(): array
    {
        if (!isset(self::$preferredLocaleResolvers)) {
            self::$preferredLocaleResolvers = [
                new SessionLocale,
                new UserLocale,
                new BrowserLocale,
            ];
        }

        return self::$preferredLocaleResolvers;
    }
}
