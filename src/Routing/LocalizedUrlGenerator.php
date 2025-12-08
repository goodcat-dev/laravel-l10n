<?php

namespace Goodcat\L10n\Routing;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;

class LocalizedUrlGenerator extends UrlGenerator
{
    public function route($name, $parameters = [], $absolute = true): string
    {
        $locale = Arr::pull($parameters, 'lang', app()->getLocale());

        if ($this->routes->hasNamedRoute("$name.$locale")) {
            $name .= ".$locale";
        }

        return parent::route($name, $parameters, $absolute);
    }
}
