<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\RouteRegistrar;

/**
 * @mixin RouteRegistrar
 */
class LocalizedRouteRegistrar
{
    /** @var array<string, mixed> */
    protected array $attributes;

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
