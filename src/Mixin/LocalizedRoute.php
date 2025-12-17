<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute as Localized;
use Illuminate\Routing\Route;

class LocalizedRoute
{
    public function lang(): Closure
    {
        return function (?array $translations = null): Route|array {
            /** @var Localized&Route $this */

            $lang = $this->action['lang'] ?? [];

            if (is_null($translations)) {
                return $lang;
            }

            $this->action['lang'] = array_unique(array_merge($lang, $translations));

            return $this;
        };
    }

    public function makeTranslations(): Closure
    {
        return function (): array {
            /** @var Localized&Route $this */

            $translations = [];

            foreach ($this->lang() as $locale) {
                $translations[$locale] = $this->makeTranslation($locale);
            }

            return $translations;
        };
    }

    public function makeTranslation(): Closure
    {
        return function (string $locale): ?Route {
            /** @var Localized&Route $this */

            if (!in_array($locale, $this->lang(), true)) {
                return null;
            }

            $action = $this->action;

            unset($action['as']);
            unset($action['prefix']);

            $key = "routes.$this->uri";

            $uri = trans()->hasForLocale($key, $locale)
                ? trans($key, locale: $locale)
                : $this->uri;

            $route = new Route($this->methods(), $uri, ['locale' => $locale] + $action);

            if (config('l10n.add_locale_prefix')) {
                $route->prefix($locale);
            }

            return $route;
        };
    }
}
