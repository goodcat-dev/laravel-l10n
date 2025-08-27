<?php

namespace Goodcat\L10n\Listeners;

use Goodcat\L10n\L10n;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\View\Factory;

class RegisterLocalizedViewsPath
{
    public function __invoke(LocaleUpdated $event): void
    {
        /** @var Factory $views */
        $views = \app('view');

        $paths = $views->getFinder()->getPaths();

        $index = array_search(L10n::$localizedViewsPath, $paths, true);

        if ($index !== false) {
            unset($paths[$index]);

            $views->getFinder()->setPaths($paths);
        }

        $newPath = \resource_path('views/' . $event->locale);

        L10n::$localizedViewsPath = is_dir($newPath) ? $newPath : '';

        if (L10n::$localizedViewsPath) {
            $views->prependLocation($newPath);
        }
    }

}