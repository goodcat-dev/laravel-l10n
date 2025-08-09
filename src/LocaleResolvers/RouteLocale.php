<?php

namespace Goodcat\L10n\LocaleResolvers;

use Illuminate\Http\Request;

class RouteLocale implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        return $request->route()->getAction('locale');
    }
}
