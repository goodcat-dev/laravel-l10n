<?php

namespace Goodcat\L10n\View\Components;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\View\Component;

class Switcher extends Component
{
    /** @var array<string, string> */
    public array $translations = [];

    public string $current = '';

    /**
     * @throws UrlGenerationException
     */
    public function __construct(UrlGenerator $url)
    {
        /** @var (LocalizedRoute&Route)|null $route */
        $route = app(Router::class)->current();

        if (! $route || count($translations = $route->getTranslations()) < 2) {
            return;
        }

        $parameters = $route->parameters() + request()->query();

        $this->current = $route->locale();

        foreach ($translations as $locale => $translation) {
            $this->translations[$locale] = $url->toRoute($translation, $parameters, true);
        }
    }

    public function render(): View|Closure|string
    {
        if (! $this->translations) {
            return '';
        }

        return view('l10n::components.switcher');
    }
}
