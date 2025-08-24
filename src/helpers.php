<?php

use Goodcat\L10n\Routing\LocalizedUrlGenerator;

if (! function_exists('l10n_route')) {
    function l10n_route(BackedEnum|string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        return app(LocalizedUrlGenerator::class)->route($name, $parameters, $absolute, $locale);
    }
}

