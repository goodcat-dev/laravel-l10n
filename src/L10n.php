<?php

namespace Goodcat\L10n;

use Goodcat\L10n\Matching\LocalizedUriValidator;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;

class L10n
{
    public static bool $hideDefaultLocale = true;

    /**
     * @return ValidatorInterface[]
     */
    public static function routeValidators(): array
    {
        return array_map(
            fn ($validator) => $validator instanceof UriValidator
                ? new LocalizedUriValidator
                : $validator,
            Route::getValidators()
        );
    }
}
