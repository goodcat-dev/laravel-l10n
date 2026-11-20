<?php

namespace Goodcat\L10n\Routing;

use Goodcat\L10n\Contracts\LocaleMatcher as LocaleMatcherContract;

/** @internal */
final class LocaleMatcher implements LocaleMatcherContract
{
    public function match(string $locale, array $availableLocales): ?string
    {
        $normalizedLocale = str_replace('-', '_', $locale);

        $language = explode('_', $normalizedLocale, 2)[0];

        $fallback = null;

        foreach ($availableLocales as $available) {
            $candidate = str_replace('-', '_', $available);

            if ($candidate === $normalizedLocale) {
                return $available;
            }

            if ($candidate === $language) {
                $fallback = $available;

                continue;
            }

            if (str_starts_with($candidate, $language.'_')) {
                $fallback ??= $available;
            }
        }

        return $fallback;
    }
}
