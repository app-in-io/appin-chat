# Changelog

## [Unreleased]

## [1.1.0] - 2026-06-30

### Added
- **Auto-open settings.** New "Behavior" section in the admin page: `Auto-open` (Never / Once per session / Every page load, default Never) and `Auto-open Delay (seconds)` (default 5). When enabled, the chat window opens itself after the configured delay following page load; "Once per session" opens only the first time per visit. Rendered as the `auto-open` / `auto-open-delay` attributes on `<app-in-chat>` (emitted only when enabled, so the default stays clean). Options: `appin_chat_auto_open`, `appin_chat_auto_open_delay`.

## [1.0.0] - 2026-04-17

### Added
- Initial plugin structure
- Admin settings page with connection, appearance, and custom colors sections
- Frontend chat widget rendering (`<app-in-chat>` web component)
- CDN loading of chat.js with ES module support
- All widget configuration options: site ID, API URL, title, subtitle, logo, theme, position, language, accent color, price prefix
- CSS custom properties override for full color theming
- `readme.txt` in WordPress.org format with external services disclosure, FAQ, screenshots, changelog, upgrade notice
- `LICENSE` file (GPL-2.0)
- `uninstall.php` to clean up all plugin options on removal
- `Text Domain` and `Domain Path` headers
- Dedicated `assets/js/settings.js` for admin color sync + media picker (replaces inline scripts)
- ABSPATH guards in all PHP files

### Changed
- Bumped `Requires at least` from 6.0 to 6.3 (required for `wp_enqueue_script` strategy array)
- Release workflow now also substitutes the version in `readme.txt` and commits before archiving (so `git archive` ships the tagged version)
- Stop appending `?ver=` to the CDN widget script URL — the JS file is versioned at the path level (`/v1/chat.js`) and cached by CDN headers; the plugin version is unrelated to the JS build

### Added (release prep)
- CI test workflow (`.github/workflows/test.yml`) — PHPUnit + Pint + PHPStan on PHP 8.1–8.4
- Deploy workflow (`.github/workflows/deploy-wordpress-org.yml`) — pushes to wordpress.org SVN on release
- Laravel Pint + PHPStan (level 5) dev dependencies with `phpstan-wordpress` stubs
- `composer ci` script = lint + analyse + test
- `SettingsPageTest` — register hooks, menu registration, enqueue, render capability check, render output (7 new tests, total 21)
- `.wordpress-org/screenshot-1.png` + `screenshot-2.png` — widget + settings page screenshots
- `readme.txt` Support section (GitHub issues + app-in.io/support)
- Updated `README.md` (developer docs) to reflect current structure + CI/CD flow
- `languages/appin-chat.pot` template + bundled translations: `en_US`, `de_DE`, `et`, `nl_NL`, `uk` (`.po` sources in repo, `.mo` shipped in zip)
