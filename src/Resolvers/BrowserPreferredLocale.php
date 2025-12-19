<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

class BrowserPreferredLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return $request->getPreferredLanguage();
    }
}
