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

            $hasLangParameter = in_array('lang', $route->parameterNames());

            if (!$hasLangParameter && in_array(null, $translations)) {
                throw new \LogicException("Missing {lang} parameter in the localized route \"$route->uri\"");
            }

            foreach (array_filter($translations) as $locale => $uri) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] =  "{$route->getName()}#$locale";
                }

                $action['prefix'] = str_replace('{lang}', $locale, $route->getPrefix());
                $action['locale'] = $locale;

                $router->addRoute($route->methods, $uri, $action);
            }

            if (L10n::$hideDefaultLocale && $hasLangParameter) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] = "{$route->getName()}#" . App::getFallbackLocale();
                }

                $action['prefix'] = '';
                $action['locale'] = App::getFallbackLocale();

                $router->addRoute(
                    $route->methods,
                    str_replace('{lang}/', '', $route->uri),
                    $action
                );
            }

            if ($hasLangParameter) {
                $route->whereIn('lang', array_keys(array_filter($translations, fn ($path) => $path === null)));
            }
        }
    }
}
