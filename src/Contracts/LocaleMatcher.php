<?php

namespace Goodcat\L10n\Contracts;

interface LocaleMatcher
{
    /** @param array<string> $availableLocales */
    public function match(string $locale, array $availableLocales): ?string;
}
