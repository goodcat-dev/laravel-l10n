# Changelog

All notable changes to this project will be documented in this file.

## v0.3.1

Released on _**2026-12-27**_.

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
