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

class Alternate extends Component
{
    /** @var array<string, string> */
    public array $alternates = [];

    public string $canonical = '';

    /**
     * @throws UrlGenerationException
     */
    public function __construct(UrlGenerator $url)
    {
        /** @var (LocalizedRoute&Route)|null $route */
        $route = app(Router::class)->current();

        $canonical = $route?->canonical();

        if (! $translations = $canonical?->makeTranslations()) {
            return;
        }

        $parameters = $route->parameters();

        $this->canonical = $url->toRoute($canonical, $parameters, true);

        $this->alternates[app()->getFallbackLocale()] = $this->canonical;

        foreach ($translations as $locale => $translation) {
            $this->alternates[$locale] = $url->toRoute($translation, $parameters, true);
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
