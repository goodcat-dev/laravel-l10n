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

    public function getKey(): Closure
    {
        return function (): string {
            /** @var Localized&Route $this */

            return implode('|', $this->methods()).$this->getDomain().$this->uri();
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
            if (! in_array($locale, $this->lang(), true)) {
                return null;
            }

            $action = ['locale' => $locale, 'canonical' => $this->getKey()] + $this->action;

//            unset($action['as']);
            unset($action['lang']);
            unset($action['prefix']);

            if ($domain = $this->getDomain()) {
                $action['domain'] = trans()->hasForLocale("routes.$domain", $locale)
                    ? trans("routes.$domain", locale: $locale)
                    : $domain;
            }

            $uri = trans()->hasForLocale("routes.$this->uri", $locale)
                ? trans("routes.$this->uri", locale: $locale)
                : $this->uri;

            $route = new Route($this->methods(), $uri, $action);

            if ($route->getName()) {
                $route->name(".$locale");
            }

            if (config('l10n.add_locale_prefix')) {
                $route->prefix($locale);
            }

            return $route
                ->setDefaults($this->defaults)
                ->setContainer($this->container)
                ->setRouter($this->router)
                ->where($this->wheres);
        };
    }
}
