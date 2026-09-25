<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\RouteRegistrar;

interface LocalizedRouter
{
    /**
     * @param  list<string>  $translations
     *
     * @see \Goodcat\L10n\Mixin\LocalizedRouter::lang
     */
    public function lang(array $translations = []): RouteRegistrar;
}
