<?php

namespace Goodcat\L10n;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;

class L10n
{
    public static bool $hideDefaultLocale = true;

    /**
     * @throws \Throwable
     */
    public static function registerLocalizedRoute(): void
    {
        if (App::routesAreCached()) {
            return;
        }

        /** @var Router $router */
        $router = App::make(Router::class);

        foreach ($router->getRoutes() as $route) {
            /** @var Route $route */

            $translations = $route->lang()->all();

            if (!$translations) {
                continue;
            }

            if (!str_contains($route->getPrefix(), '{lang}')) {
                throw new \LogicException("Missing {lang} parameter in the route prefix \"$route->uri\"");
            }

            if (L10n::$hideDefaultLocale) {
                $uri = str_replace(trim($route->getPrefix(), '/'), '', $route->uri);

                $action = [
                    'prefix' => trim(str_replace('{lang}', '', $route->getPrefix()), '/'),
                    'as' => $route->getName() . '@' . App::getFallbackLocale(),
                ];

                $router->addRoute($route->methods, $uri, array_merge($route->action, $action));
            }

            foreach (array_filter($translations) as $locale => $uri) {
                $action = ['as' => $route->getName() . "@$locale",];

                $router
                    ->addRoute($route->methods, $uri, array_merge($route->action, $action))
                    ->where('lang', $locale);
            }

            $route->whereIn('lang', array_keys(array_filter($translations, fn (?string $path) => $path === null)));
        }
    }
}
