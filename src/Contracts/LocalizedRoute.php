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

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::needsLocalization */
    public function needsLocalization(): bool;

    /**
     * The locale served by this route. A route without l10n
     * metadata counts as the fallback locale.
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::locale
     */
    public function locale(): string;

    /**
     * The registered translations and canonical route, keyed by locale. Resolves
     * through the canonical route, so it answers from any localized route.
     *
     * @return array<string, Route>
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::getTranslations
     */
    public function getTranslations(string ...$locales): array;

    /**
     * @return array<string, Route>
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslations
     */
    public function makeTranslations(): array;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslation */
    public function makeTranslation(string $locale): ?Route;
}
