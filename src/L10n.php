<?php

namespace Goodcat\L10n;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class L10n
{
    public static bool $hideDefaultLocale = true;

    public static string $localizedViewsPath = '';

    public static function registerLocalizedRoute(): void
    {
        if (\app()->routesAreCached()) {
            return;
        }

        $router = \app(Router::class);

        foreach ($router->getRoutes() as $route) {
            /** @var Route $route */

            $translations = $route->lang()->all();

            if (!$translations) {
                continue;
            }

            $fallback = \app()->getFallbackLocale();

            $hasLangParameter = in_array('lang', $route->parameterNames());

            if (!$hasLangParameter && in_array(null, $translations)) {
                throw new \LogicException("Localized route \"$route->uri\" requires {lang} parameter.");
            }

            foreach (array_filter($translations) as $locale => $uri) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] = "{$route->getName()}#$locale";
                }

                $action['prefix'] = str_replace('{lang}', $locale, $route->getPrefix());
                $action['locale'] = $locale;

                $router->addRoute($route->methods, $uri, $action);
            }

            if (self::$hideDefaultLocale && $hasLangParameter) {
                $action = $route->action;

                if ($route->getName()) {
                    $action['as'] = "{$route->getName()}#$fallback";
                }

                $action['prefix'] = '';
                $action['locale'] = $fallback;

                $uri = str_replace("{lang}/", '', $route->uri);

                $router->addRoute($route->methods, $uri, $action);

                $route->lang()->addTranslations([$fallback => $uri]);
            }

            if ($hasLangParameter) {
                $route->whereIn('lang', array_keys(array_filter($translations, fn ($path) => $path === null)));
            }
        }
    }
}
