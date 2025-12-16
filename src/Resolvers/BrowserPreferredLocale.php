<?php

namespace Goodcat\L10n\Resolvers;

use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Http\Request;

class BrowserPreferredLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        /** @var string[] $translations */
        $locales = $request->route()->lang();

        $locales[] = app()->getFallbackLocale();

        return $request->getPreferredLanguage($locales);
    }
}
