<?php

namespace Goodcat\L10n\Contracts;

interface LocalizedApplication
{
    /**
     * @param  list<string>|null  $locales
     *
     * @see \Goodcat\L10n\Mixin\LocalizedApplication::getPreferredLocale
     */
    public function getPreferredLocale(?array $locales = null): ?string;

    /**
     * @return non-empty-list<string>|null
     *
     * @see \Goodcat\L10n\Mixin\LocalizedApplication::getPreferredLocales
     */
    public function getPreferredLocales(): ?array;

    /**
     * @param  string|non-empty-list<string>  $locale
     *
     * @see \Goodcat\L10n\Mixin\LocalizedApplication::setPreferredLocale
     */
    public function setPreferredLocale(string|array $locale): void;

    /** @see \Goodcat\L10n\Mixin\LocalizedApplication::isFallbackLocale */
    public function isFallbackLocale(string $locale): bool;
}
