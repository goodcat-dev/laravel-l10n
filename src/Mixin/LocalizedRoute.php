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

    public function locale(): Closure
    {
        return function (): string {
            return $this->action['locale'] ?? app()->getFallbackLocale();
        };
    }

    public function makeTranslations(): Closure
    {
        return function (): array {
            /** @var Localized&Route $this */

            $translations = [];

            if (! $this->lang()) {
                return $translations;
            }

            $this->action['locale'] = app()->getFallbackLocale();

            $action = $this->action;

            unset($action['as']);
            unset($action['prefix']);

            foreach ($this->lang() as $locale) {
                $key = "routes.$this->uri";

                $uri = trans()->hasForLocale($key, $locale)
                    ? trans($key, locale: $locale)
                    : $this->uri;

                $translations[$locale] = new Route($this->methods(), $uri, array_merge($action, [
                    'locale' => $locale,
                    'canonical' => '',
                ]));

                if (config('l10n.add_locale_prefix')) {
                    $translations[$locale]->prefix($locale);
                }
            }

            return $translations;
        };
    }
}
