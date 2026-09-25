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
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class L10n
{
    /** @var list<LocaleResolver> */
    public static array $preferredLocaleResolvers;

    public function registerLocalizedRoutes(): void
    {
        $router = app(Router::class);

        $strategy = RouteStrategy::from(config('l10n.route_strategy'));

        $collection = new RouteCollection;

        foreach ($router->getRoutes()->getRoutes() as $route) {
            /** @var Route&LocalizedRoute $route */
            if (! $route->needsLocalization()) {
                $collection->add($route);

                continue;
            }

            if ($strategy->isPrefix()) {
                $this->prefixCanonicalRoute($route);
            }

            if (! $route->getName()) {
                $route->name('generated::'.Str::random());
            }

            $collection->add($route);

            $translations = [];

            foreach ($route->makeTranslations() as $locale => $localizedRoute) {
                $collection->add($localizedRoute);

                $translations[$locale] = $localizedRoute->getName();
            }

            $route->action['translations'] = $translations;
        }

        $router->setRoutes($collection);
    }

    /**
     * @param  Route&LocalizedRoute  $route
     */
    protected function prefixCanonicalRoute(Route $route): void
    {
        $route->action['source_uri'] = $route->uri();

        $bindingFields = $route->bindingFields();

        $route
            ->prefix(app()->getFallbackLocale())
            ->setBindingFields($bindingFields);

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
