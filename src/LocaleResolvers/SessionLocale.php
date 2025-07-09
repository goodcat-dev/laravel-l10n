<?php

namespace Goodcat\L10n\LocaleResolvers;

use Illuminate\Http\Request;

class SessionLocale implements LocaleResolverInterface
{
    public function resolve(Request $request): string
    {
        return $request->session()->get('locale', '');
    }
}