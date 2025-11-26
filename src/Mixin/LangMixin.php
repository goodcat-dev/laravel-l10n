<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;

class LangMixin
{
    public function lang(): Closure
    {
        // I'm not sure if I'm smart or dumb,
        // this feels a bit too magic...

        return function (array $translations = []): RouteRegistrar {
            /** @var Router|RouteRegistrar $this */

            if ($this::class === Router::class) {
                return (new RouteRegistrar($this))->lang($translations);
            }

            $this->attributes['lang'] = $translations;

            return $this;
        };
    }
}
