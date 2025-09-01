<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Routing\RouteTranslations;
use Illuminate\Routing\Route;

class LocalizedRoute
{
    public function lang(): Closure
    {
        return function (?array $translations = null): Route|RouteTranslations {
            /** @var Route $this */
            $lang = $this->action['lang'] ?? [];

            if (is_array($lang)) {
                $lang = new RouteTranslations($lang);
            }

            $lang->addTranslations($translations ?? []);

            $this->action['lang'] = $lang;

            return is_null($translations) ? $lang : $this;
        };
    }

    public function getLocalizedName(): Closure
    {
        return function (string $locale): ?string {
            /** @var Route $this */
            $name = $this->getName();

            if (! $name || ! $this->lang()->has($locale)) {
                return null;
            }

            if ($lang = $this->getAction('locale')) {
                $name = preg_replace("/#$lang$/", '', $name);
            }

            if ($this->lang()->hasAlias($locale)) {
                $name .= "#$locale";
            }

            return $name;
        };
    }
}
