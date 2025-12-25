<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\RouteRegistrar;

class LocalizedRouteRegistrar
{
    public function lang(): Closure
    {
        return function (array $translations = []): RouteRegistrar {
            /** @var RouteRegistrar $this */
            $this->attributes['lang'] = $translations;

            return $this;
        };
    }
}
