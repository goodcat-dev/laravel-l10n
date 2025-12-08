<?php

namespace Goodcat\L10n\Contracts;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;

interface LocalizedRoute
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::lang */
    public function lang(): Route|RouteTranslations;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::getLocalizedName */
    public function getLocalizedName(string $locale): ?string;

    /**
     * @return Route[]
     * @see \Goodcat\L10n\Mixin\LocalizedRoute::makeTranslations
     */
    public function makeTranslations(): array;

    /** @see \Goodcat\L10n\Mixin\LocalizedRoute::uriWithoutPrefix */
    public function uriWithoutPrefix(): string;
}