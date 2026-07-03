# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Changed

- Improves static analysis of the route, router, registrar and application mixins.
- Corrects localized application and router contract return types.
- Simplifies `lang()` to a write-only method.
- Documents `lang` as a reserved parameter name: routes defining their own `{lang}` parameter are not supported.
- `canonical()` now throws a `RouteNotFoundException` when the canonical route is not registered, instead of returning `null`.

### Fixed

- Preserves fallback, session blocking and trashed binding state on localized routes.
- Fixes `TypeError` when passing scalar parameters to `route()` and `action()`. E.g. `route('example', 5)`.
- No longer consumes (and removes) the `lang` attribute of models passed as route parameters. E.g. `route('example', $post)`.
- No longer writes the internal `key` action attribute on routes without `lang()`.

## v0.4.2

Released on _**2026-05-28**_

### Fixed

- Omits locale prefix from path when the domain is already translated (e.g. `es.example.com/es/ejemplo` to `es.example.com/ejemplo`).

## v0.4.1

Released on _**2026-05-21**_.

### Added

- Adds `<x-l10n::switcher />` Blade component for locale switching.
- Adds `<x-l10n::alternate />` Blade component for hreflang alternate links.
- Adds `LocalizedRoute::canonical()` method.

### Fixed

- Fixes `lang()` contract return type.
- Clarifies `add_locale_prefix` documentation.

### Removed

- Removes `getByKey()` and `refreshCanonicalLookups()` from `L10n` facade.
- Removes experimental migrate skill.

## v0.4.0

Released on _**2026-03-17**_.

### Added

- Adds experimental migrate SKILL.
- Adds Laravel 13 support.

## v0.3.3

Released on _**2026-02-07**_.

### Added

- Adds experimental support for Wayfinder and Ziggy.

## v0.3.2

Released on _**2025-12-27**_.

### Changed

- Forces `defaults`, `wheres`, `container` and `middleware` on localized routes.

## v0.3.1

Released on _**2025-12-27**_.

### Added

- Adds `L10n::is()` method for localized route matching.
- Adds `L10n` facade.
- Adds domain translations.
- Adds `SessionLocale` resolver.

## v0.3.0

Released on _**2025-12-19**_.

### Added

- Adds contracts for better type hinting.
- Adds `action()` helper.
- Adds `LocalizedRoute::makeTranslations()` method.
- Adds fluent `->lang()` method.

### Changed

- Renames `BrowserLocale` to `BrowserPreferredLocale`.
- Renames `DetectPreferredLocale` to `SetPreferredLocale`.

### Removed

- Drops support for Laravel 11.
- Removes `RouteTranslations` class.
- Removes `LocalizedRoute::getLocalizedName()` method.
- Removes localized name.
- Removes inline translations. E.g. `Route::get()->lang(['es' => 'ejemplo])`

## v0.2.0

Released on _**2025-12-04**_.

### Added

- Removes `{lang}` parameter from routes.
- Adds `l10n.php` config file.
- Auto-registers `LocalizedUrlGenerator` as `url`.

### Changed

- Moves `$localizedViewsPath` to `RegisterLocalizedViewsPath::class`.

## v0.1.3

Released on _**2025-11-25**_.

### Added

- Makes `{lang}` parameter optional.
- Sets route locale during url generation.
- Makes `{lang}` parameter invisible to controllers.
- Adds `L10n::forgetRoute()` method.

### Fixed

- Cached `{lang}` parameter during route registration.

## v0.1.2

Released on _**2025-09-22**_.

### Added

- Supports lang files.

## v0.1.1

Released on _**2025-09-01**_.

### Added

- Tests.
- Laravel Pint, CS Fixer.

## v0.1.0

Initial release.
