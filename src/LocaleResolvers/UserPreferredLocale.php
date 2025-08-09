<?php

namespace Goodcat\L10n\LocaleResolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;

class UserPreferredLocale implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        /** @var ?Authenticatable $authUser */
        $authUser = $request->user();

        if ($authUser instanceof HasLocalePreference) {
            return $authUser->preferredLocale();
        }

        return null;
    }
}
