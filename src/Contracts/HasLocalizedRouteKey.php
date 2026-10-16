<?php

namespace Goodcat\L10n\Contracts;

interface HasLocalizedRouteKey
{
    public function getLocalizedRouteKey(string $locale, ?string $field = null): ?string;
}
