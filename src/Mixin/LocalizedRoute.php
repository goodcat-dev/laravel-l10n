<?php

namespace Goodcat\L10n\Mixin;

use Closure;
use Goodcat\L10n\Contracts\LocalizedRouter;
use Goodcat\L10n\Routing\RouteStrategy;
use Illuminate\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @mixin Route
 */
class LocalizedRoute
{
    /** @var array<string, mixed> */
    public array $action;

    protected Container $container;

    /** @var array<string, mixed> */
    public array $defaults;

    public bool $isFallback;

    /** @var LocalizedRouter&Router */
    protected Router $router;

    public string $uri;

    /** @var array<string, string> */
    public array $wheres;

    /** @return Closure(): (Route|self) */
    public function canonical(): Closure
    {
        return function (): Route|self {
            if (! $canonical = $this->getAction('canonical')) {
                return $this;
            }

            return $this->router->getByKey($canonical)
                ?? throw new RouteNotFoundException("Canonical route [$canonical] not defined.");
        };
    }

    /**
     * @return Closure(list<string>=): self
     */
    public function lang(): Closure
    {
        return function (array $translations = []): self {
            $this->action['lang'] = array_unique(array_merge($this->action['lang'] ?? [], $translations));

            return $this;
        };
    }

    /**
     * The locale served by this route. A route without l10n
     * metadata counts as the fallback locale.
     *
     * @return Closure(): string
     */
    public function locale(): Closure
    {
        return function (): string {
            return $this->getAction('locale') ?? app()->getFallbackLocale();
        };
    }

    /** @return Closure(): string */
    public function getKey(): Closure
    {
        return function (): string {
            return implode('|', $this->methods()).$this->getDomain().$this->uri();
        };
    }

    /**
     * @return Closure(): array<string, Route>
     */
    public function makeTranslations(): Closure
    {
        return function (): array {
            $translations = [];

            foreach (($this->action['lang'] ?? []) as $locale) {
                if (is_string($locale)) {
                    $translation = (new LocalizedRoute)->makeTranslation()->call($this, $locale);

                    if ($translation instanceof Route) {
                        $translations[$locale] = $translation;
                    }
                }
            }

            return $translations;
        };
    }

    /** @return Closure(string): ?Route */
    public function makeTranslation(): Closure
    {
        return function (string $locale): ?Route {
            $strategy = RouteStrategy::from(config('l10n.route_strategy'));

            if (! in_array($locale, $this->action['lang'] ?? [], true)) {
                return null;
            }

            if ($locale === (new LocalizedRoute)->locale()->call($this)) {
                return null;
            }

            $action = ['locale' => $locale, 'canonical' => $this->getKey()] + $this->action;

            unset($action['lang'], $action['prefix'], $action['key'], $action['source_uri']);

            $domainWasTranslated = false;

            if ($domain = $this->getDomain()) {
                $translatedDomain = trans()->hasForLocale("routes.$domain", $locale)
                    ? trans("routes.$domain", locale: $locale)
                    : $domain;

                $action['domain'] = $translatedDomain;

                $domainWasTranslated = $translatedDomain !== $domain;
            }

            $uri = $this->getAction('source_uri') ?? $this->uri;

            $uri = trans()->hasForLocale("routes.$uri", $locale)
                ? trans("routes.$uri", locale: $locale)
                : $uri;

            $route = new Route($this->methods(), $uri, $action);

            if ($route->getName()) {
                $route->name(".$locale");
            }

            if ($strategy !== RouteStrategy::NoPrefix && ! $domainWasTranslated) {
                $route->prefix($locale);
            }

            return $route
                ->setFallback($this->isFallback)
                ->setDefaults($this->defaults)
                ->setContainer($this->container)
                ->setRouter($this->router)
                ->block($this->locksFor(), $this->waitsFor())
                ->withTrashed($this->allowsTrashedBindings())
                ->where($this->wheres);
        };
    }
}
