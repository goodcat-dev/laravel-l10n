<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

interface LocaleResolver
{
    /** @return string|list<string>|null */
    public function resolve(Request $request): string|array|null;
}
