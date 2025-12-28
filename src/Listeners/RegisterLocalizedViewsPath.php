<?php

namespace Goodcat\L10n\Listeners;

use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\View\Factory;

class RegisterLocalizedViewsPath
{
    public function __invoke(LocaleUpdated $event): void
    {
        /** @var Factory $views */
        $views = \app('view');

        $paths = $views->getFinder()->getPaths();

        $oldPath = $event->previousLocale ? resource_path('views/'.$event->previousLocale) : '';

        $index = array_search($oldPath, $paths, true);

        if ($index !== false) {
            unset($paths[$index]);

            $views->getFinder()->setPaths($paths);
        }

        $newPath = \resource_path('views/'.$event->locale);

        if (is_dir($newPath)) {
            $views->prependLocation($newPath);
        }
    }
}
