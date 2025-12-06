<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;

class LocalizedRoute
{
    public function lang(): Closure
    {
        return function (?array $translations = null): Route|RouteTranslations {
            /** @var Route $this */

            $lang = $this->action['lang'] ?? [];

            if (is_array($lang)) {
                $lang = new RouteTranslations($lang);
            }

            $lang->addTranslations($translations ?? []);

            $this->action['lang'] = $lang;

            return is_null($translations) ? $lang : $this;
        };
    }

    public function getLocalizedName(): Closure
    {
        return function (string $locale): ?string {
            /** @var Route $this */

            $name = $this->getName();
            /** @var RouteTranslations $translations */
            $translations = $this->lang();

            if (! $name || ! $translations->has($locale)) {
                return null;
            }

            if ($lang = $this->getAction('locale')) {
                $name = preg_replace("/#$lang$/", '', $name);
            }

            if (! app()->isFallbackLocale($locale)) {
                $name .= "#$locale";
            }

            return $name;
        };
    }

    public function makeTranslations(): Closure
    {
        return function (): array {
            /** @var Route $route */
            $route = clone $this;

            /** @var RouteTranslations $translations */
            $translations = $this->lang();

            $localizedRoutes = [];

            if (! in_array('lang', $route->parameterNames(), true)) {
                $route->prefix('{lang}');
            }

            $prefix = $route->getPrefix() ?? '';

            foreach ($translations->all() as $locale => $uri) {
                $action = $route->action;

                $action['locale'] = $locale;

                $uri ??= preg_replace("#^$prefix#", '', $route->uri());

                $isFallbackLocale = app()->isFallbackLocale($locale);

                if (($name = $route->getName()) && ! $isFallbackLocale) {
                    $action['as'] = "$name#$locale";
                }

                if (
                    (config('l10n.hide_alias_locale') && $translations->hasAlias($locale))
                    || ($isFallbackLocale && config('l10n.hide_default_locale'))
                ) {
                    $locale = '';
                }

                $uri = preg_replace('#/+#', '/', str_replace('{lang}', $locale, $uri));

                $action['prefix'] = preg_replace('#/+#', '/', str_replace('{lang}', $locale, $prefix));

                $localizedRoutes[] = new Route($route->methods(), $uri, $action);
            }

            return $localizedRoutes;
        };
    }
}
