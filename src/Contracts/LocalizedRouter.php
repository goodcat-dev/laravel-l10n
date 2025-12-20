<?php

namespace Goodcat\L10n\Contracts;

interface LocalizedRouter
{
    /** @see \Goodcat\L10n\Mixin\LocalizedRouter::lang */
    public function lang(array $translations): void;
}
