<?php

namespace Goodcat\L10n\Routing;

use Illuminate\Routing\Route;

class RouteTranslations
{
    protected array $lang = [];

    public function __construct(array $translations)
    {
        $this->addTranslations($translations);
    }

    public function addTranslations(array $translations = []): self
    {
        foreach ($translations as $locale => $translation) {
            is_int($locale)
                ? $this->lang[$translation] = null
                : $this->lang[$locale] = $translation;
        }

        return $this;
    }

    public function hasTranslation(string $locale): bool
    {
        return in_array($locale, $this->locales());
    }

    public function get(string $locale): ?string
    {
        return $this->lang[$locale] ?? null;
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return array_keys($this->lang);
    }

    public function isEmpty(): bool
    {
        return empty($this->lang);
    }

    public static function __set_state(array $attributes): self
    {
        return new self($attributes['lang']);
    }

    public function guessLocaleFromPath(string $uri, string $path): ?string
    {
        $locales = $this->locales();

        if (!$locales) {
            return null;
        }

        $length = max(array_map('strlen', $locales));

        $portion = substr($path, strpos("/$uri", '/{lang}'), $length + 2);

        foreach ($locales as $locale) {
            if (str_contains($portion, "/$locale/")) {
                return $locale;
            }
        }

        return null;
    }

    public function replaceUriWithTranslation(Route $route, string $locale): void
    {
        if (! $uri = $this->get($locale)) {
            return;
        }

        $localized = new Route($route->methods, $uri, $route->action);

        $route->action['original_uri'] = $route->uri;

        $route->setUri($localized->uri);

        $route->compiled = $route->toSymfonyRoute()->compile();
    }
}
