<?php

namespace Goodcat\L10n\Routing;

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

    public function has(string $locale): bool
    {
        return isset($this->lang[$locale]);
    }

    public function hasAlias(?string $locale = null): bool
    {
        $aliases = array_filter($this->lang);

        if ($locale === null) {
            return !empty($aliases);
        }

        return array_key_exists($locale, $aliases);
    }

    public function hasGeneric(?string $locale = null): bool
    {
        $generics = array_filter($this->lang, fn ($translation) => $translation === null);

        if ($locale === null) {
            return !empty($generics);
        }

        return array_key_exists($locale, $generics);
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
     * @return list<string>
     */
    public function genericLocales(): array
    {
        return array_keys(array_filter($this->lang, fn ($translation) => $translation === null));
    }

    /**
     * @return list<string>
     */
    public function aliasLocales(): array
    {
        return array_keys(array_filter($this->lang));
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
