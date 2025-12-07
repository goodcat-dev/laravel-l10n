<?php

namespace Goodcat\L10n\Contracts;

interface LocalizedApplication
{
    /** @see \Goodcat\L10n\Mixin\LocalizedApplication::getPreferredLocale */
    public function getPreferredLocale(): string;

    /** @see \Goodcat\L10n\Mixin\LocalizedApplication::setPreferredLocale */
    public function setPreferredLocale(string $locale): void;

    /** @see \Goodcat\L10n\Mixin\LocalizedApplication::isFallbackLocale */
    public function isFallbackLocale(string $locale): bool;
}