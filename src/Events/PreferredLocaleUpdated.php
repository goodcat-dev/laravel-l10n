<?php

namespace Goodcat\L10n\Events;

class PreferredLocaleUpdated
{
    /**
     * @param  non-empty-list<string>  $locales
     * @param  non-empty-list<string>|null  $previousLocales
     */
    public function __construct(
        public array $locales,
        public ?array $previousLocales = null
    ) {}
}
