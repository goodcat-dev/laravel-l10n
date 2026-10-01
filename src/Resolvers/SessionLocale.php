<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

/** @deprecated Implement a custom LocaleResolver for session-based preferences. */
class SessionLocale implements LocaleResolver
{
    public function resolve(Request $request): ?string
    {
        return $request->session()->get('locale');
    }
}
