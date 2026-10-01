<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Events\PreferredLocaleUpdated;

class LocalizedApplication
{
    /** @return Closure(list<string>|null=): ?string */
    public function getPreferredLocale(): Closure
    {
        return function (?array $locales = null): ?string {
            /** @var list<string> $preferred */
            $preferred = config('app.preferred_locales') ?? [];

            if ($locales === null) {
                return $preferred[0] ?? null;
            }

            foreach ($preferred as $locale) {
                $language = strstr($locale, '_', true) ?: $locale;

                foreach ([$locale, $language] as $candidate) {
                    if (in_array($candidate, $locales, true)) {
                        return $candidate;
                    }
                }

                foreach ($locales as $available) {
                    if (str_starts_with($available, $language.'_')) {
                        return $available;
                    }
                }
            }

            return null;
        };
    }

    /** @return Closure(): (non-empty-list<string>|null) */
    public function getPreferredLocales(): Closure
    {
        return function (): ?array {
            /** @var non-empty-list<string>|null */
            return config('app.preferred_locales');
        };
    }

    /** @return Closure(string|non-empty-list<string>): void */
    public function setPreferredLocale(): Closure
    {
        return function (string|array $locale): void {
            /** @var non-empty-list<string> $locales */
            $locales = (array) $locale;

            $previous = config('app.preferred_locales');

            config(['app.preferred_locales' => $locales]);

            event(new PreferredLocaleUpdated($locales, $previous));
        };
    }

    /** @return Closure(string): bool */
    public function isFallbackLocale(): Closure
    {
        return function (string $locale): bool {
            return config('app.fallback_locale') === $locale;
        };
    }
}
