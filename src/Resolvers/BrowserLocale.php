<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

class BrowserLocale implements LocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return $request->getPreferredLanguage();
    }
}
