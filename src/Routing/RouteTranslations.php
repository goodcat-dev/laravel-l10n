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

    public function get(string $locale): ?string
    {
        return $this->lang[$locale] ?? null;
    }

    public function has(string $locale): bool
    {
        return array_key_exists($locale, $this->lang);
    }

    public function hasAlias(string $locale): bool
    {
        return array_key_exists($locale, array_filter($this->lang));
    }

    public function all(): array
    {
        return $this->lang;
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
}
