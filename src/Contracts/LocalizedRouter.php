<?php

namespace Goodcat\L10n\Contracts;

use Illuminate\Routing\Route;

interface LocalizedRouter
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRouter::forget */
    public function forget(Route $route): void;
}