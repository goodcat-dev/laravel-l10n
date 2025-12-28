<?php

namespace Goodcat\L10n\Events;

class PreferredLocaleUpdated
{
    public function __construct(
        public string $locale,
        public ?string $previousLocale = null
    ) {}
}
