<?php

namespace Goodcat\L10n\Listeners;

use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class RegisterLocalizedViewsPath
{
    public function __invoke(LocaleUpdated $event): void
    {
        /** @var Factory $views */
        $views = \app('view');

        /** @var FileViewFinder $finder */
        $finder = $views->getFinder();

        $paths = $finder->getPaths();

        $oldPath = $event->previousLocale ? resource_path('views/'.$event->previousLocale) : '';

        $index = array_search($oldPath, $paths, true);

        if ($index !== false) {
            unset($paths[$index]);

            $finder->setPaths($paths);
        }

        $newPath = \resource_path('views/'.$event->locale);

        if (is_dir($newPath)) {
            $views->prependLocation($newPath);
        }
    }
}
