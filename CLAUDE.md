# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel L10n (`goodcat/laravel-l10n`) is an opinionated Laravel package for application localization. It provides localized routes, locale-specific views, preferred locale detection, and JavaScript URL generation helpers (Ziggy/Wayfinder stubs).

Requires PHP ^8.2 and Laravel ^12.44.0. Uses Orchestra Testbench for testing.

## Commands

```bash
# Run tests
vendor/bin/pest --ci

# Run a single test
vendor/bin/pest --filter "test name"

# Static analysis (level 7)
vendor/bin/phpstan

# Code formatting
vendor/bin/pint
```

## Architecture

### Mixin Pattern

The package extends Laravel core classes (Route, Router, RouteRegistrar, Application) via **Mixins** rather than inheritance. Contracts in `src/Contracts/` define the interfaces; implementations live in `src/Mixin/`. These are registered in `L10nServiceProvider`. PHPStan is configured to ignore these dynamically-added methods in `phpstan.neon`.

### Route Localization Flow

1. Routes are marked with `->lang(['es', 'it'])` which stores supported locales in the route action
2. On boot, `L10n::registerLocalizedRoutes()` iterates all routes and calls `makeTranslations()` on localized ones
3. For each locale, a new Route is created with translated URI (from lang files), locale prefix, and metadata linking back to the canonical route
4. `LocalizedUrlGenerator` (extends Laravel's UrlGenerator, aliased to `url`) intercepts URL generation to resolve the correct localized route version based on a `lang` parameter or the current locale

### Preferred Locale Detection

Three resolvers run in order via `SetPreferredLocale` middleware:
- `SessionLocale` → `UserLocale` → `BrowserLocale`

Resolvers are customizable via `L10n::$preferredLocaleResolvers`.

### Localized Views

`RegisterLocalizedViewsPath` listener reacts to Laravel's `LocaleUpdated` event and prepends a locale-specific view path (e.g., `resources/views/it/`) to the view finder.

### JavaScript Stubs

Publishable stubs in `stubs/` wrap Ziggy (`l10n.js`) and Wayfinder (`l10n.ts`) to append locale to route name resolution.

## Testing

Tests use Pest with Orchestra Testbench. Test support files (controllers, models, lang files) live in `tests/Support/`. The CI matrix tests PHP 8.2/8.3/8.4 with Laravel 12.
