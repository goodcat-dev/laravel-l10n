<?php

namespace Goodcat\L10n\Listeners;

use Goodcat\L10n\L10n;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\View\Factory;

class RegisterLocalizedViewsPath
{
    protected string $currentPath = '';

    public function __invoke(LocaleUpdated $event): void
    {
        /** @var Factory $views */
        $views = \app('view');

        $paths = $views->getFinder()->getPaths();

        $index = array_search($this->currentPath, $paths, true);

        if ($index !== false) {
            unset($paths[$index]);

            $views->getFinder()->setPaths($paths);
        }

        $newPath = \resource_path('views/'.$event->locale);

        $this->currentPath = is_dir($newPath) ? $newPath : '';

        if ($this->currentPath) {
            $views->prependLocation($newPath);
        }
    }
}
