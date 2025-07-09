<?php

namespace Goodcat\L10n\LocaleResolvers;

use Illuminate\Http\Request;

interface LocaleResolverInterface
{
    public function resolve(Request $request): string;
}