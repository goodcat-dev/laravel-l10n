<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

class BrowserLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        $translations = $request->route()->lang();

        return $request->getPreferredLanguage($translations->locales());
    }
}
