<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\Route;

interface LocalizedRouter
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRouter::lang */
    public function lang(array $translations): void;

    /** @see \Goodcat\L10n\Mixin\LocalizedRouter::getByKey */
    public function getByKey(string $key): ?Route;
}
