<?php

namespace Goodcat\L10n\Resolvers;

use Illuminate\Http\Request;

interface PreferredLocaleResolver
{
    public function resolve(Request $request): ?string;
}
