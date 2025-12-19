<?php

namespace Goodcat\L10n\Middleware;

use Closure;
use Goodcat\L10n\L10n;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPreferredLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resolvers = L10n::getPreferredLocaleResolvers();

        foreach ($resolvers as $resolver) {
            if ($locale = $resolver->resolve($request)) {
                app()->setPreferredLocale($locale);

                break;
            }
        }

        return $next($request);
    }
}
