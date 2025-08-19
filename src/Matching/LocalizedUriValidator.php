<?php

namespace Goodcat\L10n\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;

class LocalizedUriValidator extends UriValidator
{
    public function matches(Route $route, Request $request): bool
    {
        $matches = parent::matches($route, $request);

        $segments = count(explode('/', $route->uri));

        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        if (
            !$matches
            && $route->getAction('lang')
            && count($request->segments()) <= --$segments
        ) {
            $path = substr_replace($path, '/' . App::getFallbackLocale(), strpos($route->uri, '{lang}'), 0);

            $matches = preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
        }

        return $matches;
    }

}