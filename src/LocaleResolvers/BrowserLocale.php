<?php

namespace Goodcat\L10n\LocaleResolvers;

use Illuminate\Http\Request;

class BrowserLocale implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        $locales = array_intersect($request->getLanguages(), config('app.available_locales', []));

        return array_pop($locales);
    }
}