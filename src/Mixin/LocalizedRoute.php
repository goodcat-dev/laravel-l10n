<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute as Localized;
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

    public function uriWithoutPrefix(): Closure
    {
        return function (): string {
            /** @var Route $this */

            $prefix = preg_quote($this->getPrefix(), '#');

            $uriWithoutPrefix = preg_replace("#^$prefix#", '', $this->uri());

            return trim($uriWithoutPrefix, '/');
        };
    }

    public function makeTranslations(): Closure
    {
        return function (): array {
            /** @var Localized&Route $this */
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

                $uri ??= $route->uriWithoutPrefix();

                $isFallbackLocale = app()->isFallbackLocale($locale);

                if (($name = $route->getName())) {
                    $action['as'] = "$name.$locale";
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
