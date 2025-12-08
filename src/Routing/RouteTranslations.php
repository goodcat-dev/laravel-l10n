<?php

namespace Goodcat\L10n\Routing;

use Goodcat\L10n\Contracts\LocalizedRoute;
use Illuminate\Routing\Route;

class RouteTranslations
{
    /** @var array<string, ?string> */
    protected array $lang = [];

    /**
     * @param array<array-key, string> $translations
     */
    public function __construct(array $translations)
    {
        $this->addTranslations($translations);
    }

    /**
     * @param array<array-key, string> $translations
     * @return $this
     */
    public function addTranslations(array $translations = []): self
    {
        foreach ($translations as $locale => $translation) {
            if ($translation !== null) {
                $translation = trim($translation, '/');
            }

            is_int($locale)
                ? $this->lang[$translation] = null
                : $this->lang[$locale] = $translation;
        }

        return $this;
    }

    /**
     * @param Route&LocalizedRoute $route
     * @return $this
     */
    public function fillMissing(Route $route): self
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $uri = $route->uriWithoutPrefix();

        $key = "routes.$uri";

        $translations = [
            app()->getFallbackLocale() => config('l10n.hide_default_locale') ? $uri : null
        ];

        $genericLocales = array_keys(array_filter($this->lang, fn ($translation) => $translation === null));

        foreach ($genericLocales as $locale) {
            if (trans()->hasForLocale($key, $locale)) {
                $translations[$locale] = trans($key, locale: $locale);
            }
        }

        $this->addTranslations($translations);

        return $this;
    }

    public function hasAlias(string $locale): bool
    {
        $aliases = array_filter($this->lang);

        return array_key_exists($locale, $aliases);
    }

    public function isEmpty(): bool
    {
        return empty($this->lang);
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return array_keys($this->lang);
    }

    /**
     * @return array<string, ?string>
     */
    public function all(): array
    {
        return $this->lang;
    }

    public static function __set_state(array $attributes): self
    {
        return new self($attributes['lang']);
    }
}
