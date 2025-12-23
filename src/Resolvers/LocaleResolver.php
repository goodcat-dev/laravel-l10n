<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

interface LocaleResolver
{
    public function resolve(Request $request): ?string;
}
