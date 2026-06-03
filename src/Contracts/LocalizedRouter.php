<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteRegistrar;

interface LocalizedRouter
{
    /**
     * @param  list<string>  $translations
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRouter::lang
     */
    public function lang(array $translations = []): RouteRegistrar;

    /** @see \Goodcat\L10n\Mixin\LocalizedRouter::getByKey */
    public function getByKey(string $key): ?Route;
}
