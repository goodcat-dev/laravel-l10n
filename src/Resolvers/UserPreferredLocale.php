<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;

class UserPreferredLocale implements PreferredLocaleResolver
{
    public function resolve(Request $request): ?string
    {
        $authUser = $request->user();

        if ($authUser instanceof HasLocalePreference) {
            return $authUser->preferredLocale();
        }

        return null;
    }
}
