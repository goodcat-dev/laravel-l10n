<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;

class LocalizedRouter
{
    public function lang(): Closure
    {
        return function (array $translations = []): RouteRegistrar {
            /** @var Router $this */

            return (new RouteRegistrar($this))->lang($translations);
        };
    }
}
