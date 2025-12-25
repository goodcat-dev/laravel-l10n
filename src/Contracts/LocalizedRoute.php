<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\Route;

interface LocalizedRoute
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::lang */
    public function lang(): Route;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::getKey */
    public function getKey(): string;

    /**
     * @return Route[]
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslations
     */
    public function makeTranslations(): array;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslation */
    public function makeTranslation(string $locale): ?Route;
}
