<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Events\PreferredLocaleUpdated;
use Illuminate\Foundation\Application;

class LocalizedApplication
{
    public function getPreferredLocale(): Closure
    {
        return function (): ?string {
            /** @var Application $this */

            return $this['config']->get('app.preferred_locale');
        };
    }

    public function setPreferredLocale(): Closure
    {
        return function (string $locale): void {
            /** @var Application $this */
            $previous = $this['config']->get('app.preferred_locale');

            $this['config']->set('app.preferred_locale', $locale);

            $this['events']->dispatch(new PreferredLocaleUpdated($locale, $previous));
        };
    }

    public function isFallbackLocale(): Closure
    {
        return function (string $locale): bool {
            /** @var Application $this */

            return $this->getFallbackLocale() === $locale;
        };
    }
}
