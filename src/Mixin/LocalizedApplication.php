<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Events\PreferredLocaleUpdated;

class LocalizedApplication
{
    /** @return Closure(): ?string */
    public function getPreferredLocale(): Closure
    {
        return function (): ?string {
            return config('app.preferred_locale');
        };
    }

    /** @return Closure(string): void */
    public function setPreferredLocale(): Closure
    {
        return function (string $locale): void {
            $previous = config('app.preferred_locale');

            config(['app.preferred_locale' => $locale]);

            event(new PreferredLocaleUpdated($locale, $previous));
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
