<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

class BrowserLocale implements LocaleResolver
{
    /**
     * The Accept-Language languages in order of preference. Request::getLanguages()
     * keeps the "*" wildcard, which is not a locale, and deduplicates languages
     * with array_unique(), leaving gaps in the keys.
     *
     * @return ?list<string>
     */
    public function resolve(Request $request): ?array
    {
        return array_values(array_diff($request->getLanguages(), ['*'])) ?: null;
    }
}
