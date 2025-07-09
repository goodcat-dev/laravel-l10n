<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Goodcat\L10n\L10n;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (L10n::getLocaleResolvers() as $resolver) {
            $locale = $resolver->resolve($request);

            if ($locale) {
                App::setLocale($locale);

                break;
            }
        }

        return $next($request);
    }
}
