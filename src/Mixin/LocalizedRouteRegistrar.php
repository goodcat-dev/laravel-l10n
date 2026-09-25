<?php

namespace Goodcat\L10n\Mixin;

use AllowDynamicProperties;
use Closure;
use Illuminate\Routing\RouteRegistrar;

/**
 * @mixin RouteRegistrar
 *
 * @property array<string, mixed> $attributes
 */
#[AllowDynamicProperties]
class LocalizedRouteRegistrar
{
    /**
     * @return Closure(list<string>=): (RouteRegistrar|self)
     */
    public function lang(): Closure
    {
        return function (array $translations = []) {
            $this->attributes['lang'] = $translations;

            return $this;
        };
    }
}
