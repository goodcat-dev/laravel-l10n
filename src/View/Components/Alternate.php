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

class Alternate extends Component
{
    /** @var array<string, string> */
    public array $alternates = [];

    public string $canonical = '';

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

        $this->alternates = $url->toTranslations($route, $route->parameters() + request()->query());

        $this->canonical = $this->alternates[$route->canonical()->locale()] ?? '';

        if (count($this->alternates) < 2) {
            $this->alternates = [];
        }
    }

    public function render(): View|Closure|string
    {
        if (! $this->alternates) {
            return '';
        }

        return view('l10n::components.alternate');
    }
}
