<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\Route;

interface LocalizedRoute
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::canonical */
    public function canonical(): Route;

    /**
     * @param  list<string>  $translations
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::lang
     */
    public function lang(array $translations = []): Route;

    /**
     * The locale served by this route. A route without l10n
     * metadata counts as the fallback locale.
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::locale
     */
    public function locale(): string;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::getKey */
    public function getKey(): string;

    /**
     * @return array<string, Route>
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslations
     */
    public function makeTranslations(): array;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslation */
    public function makeTranslation(string $locale): ?Route;
}
