<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;

class LangMixin
{
    public function lang(): Closure
    {
        return function (array $translations = []): RouteRegistrar {
            /** @var RouteRegistrar $this */

            if ($this::class === Router::class) {
                return (new RouteRegistrar($this))->lang($translations);
            }

            $this->attributes['lang'] = $translations;

            return $this;
        };
    }
}
