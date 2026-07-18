<?php

namespace Goodcat\L10n\Routing;

enum RouteStrategy: string
{
    case NoPrefix = 'no_prefix';
    case Prefix = 'prefix';
    case PrefixExceptDefault = 'prefix_except_default';

    public function isPrefix(): bool
    {
        return $this === self::Prefix;
    }
}
