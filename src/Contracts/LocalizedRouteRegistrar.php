<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\RouteRegistrar;

interface LocalizedRouteRegistrar
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRouteRegistrar::lang */
    public function lang(array $translations): RouteRegistrar;
}