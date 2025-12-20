<?php

namespace Goodcat\L10n\Facades;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getPreferredLocaleResolvers()
 * @method static void registerLocalizedRoutes()
 * @method static bool is(string ...$patterns)
 * @method static Route|null getByKey(string $key)
 * @method static void refreshCanonicalLookups()
 *
 * @see \Goodcat\L10n\L10n
 */
class L10n extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Goodcat\L10n\L10n::class;
    }
}