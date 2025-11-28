<?php

namespace Goodcat\L10n\Routing;

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
            is_int($locale)
                ? $this->lang[$translation] = null
                : $this->lang[$locale] = $translation;
        }

        return $this;
    }

    public function has(string $locale): bool
    {
        return array_key_exists($locale, $this->lang);
    }

    public function hasAlias(?string $locale = null): bool
    {
        $aliases = array_filter($this->lang);

        if ($locale === null) {
            return ! empty($aliases);
        }

        return array_key_exists($locale, $aliases);
    }

    public function hasGeneric(?string $locale = null): bool
    {
        $generics = array_filter($this->lang, fn ($translation) => $translation === null);

        if ($locale === null) {
            return ! empty($generics);
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
        $genericLocales = array_keys(array_filter($this->lang, fn ($translation) => $translation === null));

        unset($genericLocales[app()->getFallbackLocale()]);

        return $genericLocales;
    }

    /**
     * @return array<string, string>
     */
    public function aliasLocales(): array
    {
        $aliasLocales = array_filter($this->lang);

        unset($aliasLocales[app()->getFallbackLocale()]);

        return $aliasLocales;
    }

    /**
     * @return array{string, ?string}
     */
    public function fallbackLocale(): array
    {
        $fallbackLocale = app()->getFallbackLocale();

        return [$fallbackLocale, $this->lang[$fallbackLocale]];
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
