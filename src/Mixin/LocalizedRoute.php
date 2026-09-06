<?php

namespace Goodcat\L10n\Mixin;

use AllowDynamicProperties;
use Closure;
use Goodcat\L10n\Contracts\LocalizedRouter;
use Goodcat\L10n\Routing\RouteStrategy;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @mixin Route
 *
 * @property array<string, mixed> $action
 * @property mixed $compiled
 * @property ?list<string> $parameterNames
 * @property ?array<string, mixed> $parameters
 * @property ?array<string, mixed> $originalParameters
 * @property LocalizedRouter&Router $router
 */
#[AllowDynamicProperties]
class LocalizedRoute
{
    /** @return Closure(): (Route|self) */
    public function canonical(): Closure
    {
        return function (): Route|self {
            if (! $canonical = $this->getAction('canonical')) {
                return $this;
            }

            return $this->router->getRoutes()->getByName($canonical)
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

    /** @return Closure(): bool */
    public function needsLocalization(): Closure
    {
        return function (): bool {
            return (bool) $this->getAction('lang')
                && ! $this->getAction('canonical')
                && $this->getAction('translations') === null;
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

    /**
     * The registered translations and canonical route, keyed by locale. Resolves
     * through the canonical route, so it answers from any localized route.
     *
     * @return Closure(string...): array<string, Route>
     */
    public function getTranslations(): Closure
    {
        return function (string ...$locales): array {
            /** @var Route $canonical */
            $canonical = (new LocalizedRoute)->canonical()->call($this);

            $canonicalLocale = $canonical->locale();

            /** @var array<string, string> $names */
            $names = $canonical->getAction('translations') ?? [];

            $translations = [];

            foreach ($locales ?: [$canonicalLocale, ...array_keys($names)] as $locale) {
                if ($locale === $canonicalLocale) {
                    $translations[$locale] = $canonical;

                    continue;
                }

                if (! isset($names[$locale])) {
                    continue;
                }

                $translations[$locale] = $this->router->getRoutes()->getByName($names[$locale])
                    ?? throw new RouteNotFoundException("Translated route [{$names[$locale]}] not defined.");
            }

            return $translations;
        };
    }

    /**
     * @return Closure(): array<string, Route>
     */
    public function makeTranslations(): Closure
    {
        return function (): array {
            $translations = [];

            $uris = [$this->getDomain().$this->uri()];

            foreach (($this->action['lang'] ?? []) as $locale) {
                $translation = (new LocalizedRoute)->makeTranslation()->call($this, $locale);

                if ($translation instanceof Route) {
                    $uri = $translation->getDomain().$translation->uri();

                    if (in_array($uri, $uris, true)) {
                        continue;
                    }

                    $uris[] = $uri;

                    $translations[$locale] = $translation;
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

            $action = ['locale' => $locale, 'canonical' => $this->getName()] + $this->action;

            unset($action['lang'], $action['prefix'], $action['source_uri'], $action['translations']);

            $domainWasTranslated = false;

            if ($domain = $this->getDomain()) {
                $translatedDomain = trans()->hasForLocale("routes.$domain", $locale)
                    ? trans("routes.$domain", locale: $locale)
                    : $domain;

                $action['domain'] = $translatedDomain;

                $domainWasTranslated = $translatedDomain !== $domain;
            }

            $uri = $this->getAction('source_uri') ?? $this->uri();

            $uri = trans()->hasForLocale("routes.$uri", $locale)
                ? trans("routes.$uri", locale: $locale)
                : $uri;

            $route = clone $this;

            $route->action = $action;

            foreach (['compiled', 'parameters', 'parameterNames', 'originalParameters'] as $property) {
                $route->$property = null;
            }

            $route->setUri(trim($uri, '/') ?: '/')->flushController();

            if ($name = $route->getName()) {
                $route->action['as'] = str_starts_with($name, 'generated::')
                    ? 'generated::'.Str::random()
                    : "$name.$locale";
            }

            if ($strategy !== RouteStrategy::NoPrefix && ! $domainWasTranslated) {
                $route->prefix($locale);
            }

            return $route->setBindingFields($this->bindingFields());
        };
    }
}
