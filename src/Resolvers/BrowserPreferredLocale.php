<?php

namespace Goodcat\L10n\Resolvers;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Http\Request;

class BrowserPreferredLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        /** @var RouteTranslations $translations */
        $translations = $request->route()->lang();

        return $request->getPreferredLanguage($translations->locales());
    }
}
