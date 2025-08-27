<?php

namespace Goodcat\L10n\Listeners;

use Illuminate\Routing\Events\RouteMatched;

class SetLocale
{
    public function __invoke(RouteMatched $event): void
    {
        $locale = $event->route->parameter(
            'lang',
            $event->route->getAction('locale')
        );

        if ($locale) {
            \app()->setLocale($locale);
        }
    }
}