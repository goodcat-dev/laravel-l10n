<?php

namespace Goodcat\L10n\Resolvers;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Http\Request;

class BrowserLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        /** @var RouteTranslations $route */
        $translations = $request->route()->lang();

        $locales = array_intersect($request->getLanguages(), $translations->locales());

        return array_pop($locales);
    }
}