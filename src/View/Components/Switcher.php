<?php

namespace Goodcat\L10n\View\Components;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRoute;
use Goodcat\L10n\Routing\LocalizedUrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\View\Component;

class Switcher extends Component
{
    /** @var array<string, string> */
    public array $translations = [];

    public string $current = '';

    /**
     * @throws UrlGenerationException
     */
    public function __construct(LocalizedUrlGenerator $url)
    {
        /** @var (LocalizedRoute&Route)|null $route */
        $route = app(Router::class)->current();

        if (! $route || count($route->getTranslations()) < 2) {
            return;
        }

        $this->current = $route->locale();

        $this->translations = $url->toTranslations($route, $route->parameters() + request()->query());

        if (count($this->translations) < 2) {
            $this->translations = [];
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
