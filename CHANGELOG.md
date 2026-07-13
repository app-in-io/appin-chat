# Changelog

## [Unreleased]

## [1.3.0] - 2026-07-13

### Changed
- **WordPress.org unique-prefix compliance.** The directory review pended the plugin with *"Generic function/class/define/namespace/option names — this plugin is using the prefixes `appin_chat`, `app_in_chat` for 11 element(s). Using the common word 'app' as a prefix."* Everything is renamed onto the `appinio` prefix, matching what `appin-search` did for the same demand:
  - Namespace `AppIn\Chat` → `AppInIo\Chat` (and the manual `autoload.php` PSR-4 prefix with it).
  - All 21 options `appin_chat_*` → `appinio_chat_*`; settings group `appin_chat` → `appinio_chat`; settings sections likewise.
  - JS global `AppInChatSettings` → `AppInIoChatSettings`.
  - The plugin slug, text domain, and script handles stay `appin-chat` — a plugin's own slug is always an acceptable prefix, and the slug is reserved on WordPress.org.
- **The plugin now defines zero global constants.** `APPIN_CHAT_CDN_URL`, `APPIN_CHAT_PLUGIN_FILE`, `APPIN_CHAT_PLUGIN_DIR`, `APPIN_CHAT_PLUGIN_URL` and `APPIN_CHAT_VERSION` are all gone — a `define()` on a rejected prefix is exactly what the review flagged, and none of them needed to be global:
  - The widget script URL now resolves through the **`appinio_chat_cdn_url` filter** (production default baked into `Frontend\ChatWidget::cdnUrl()`), mirroring `appinio_cdn_url` in `appin-search`. This is the sole override seam; the dev harness uses it to target the local Vite server.
  - The plugin path is `Plugin::file()` (set from `Plugin::boot(__FILE__)`), the version is `Plugin::VERSION`.
  - `phpstan-stubs.php` existed only to declare those constants and is deleted.
- **WPML admin-text translations do not survive the option rename.** `wpml-config.xml` declares admin texts by option name, and WPML keys their translations the same way, so the chat title, subtitle, and price prefix must be re-translated once in WPML → String Translation. Polylang is unaffected: `pll__()` resolves by the source string's value, not by the name it was registered under.
- **A `define('APPIN_CHAT_CDN_URL', …)` in `wp-config.php` is no longer honored.** The old constant was read behind a `! defined()` guard, which made it an override seam; there is no back-compat read. Use the `appinio_chat_cdn_url` filter instead.

### Added
- **One-time option migration (`src/Migration.php`).** On upgrade, every `appin_chat_*` row is copied to its `appinio_chat_*` name and the old row deleted, gated on the `appinio_chat_version` option so it runs once. Existing installs keep their Site ID, theming, and behavior settings. An empty-string value is preserved (distinguished from an absent row); a value already present under the new name is never overwritten.
- **`src/Options.php`** — single source of truth for every option the plugin owns. `SettingsPage`, `Migration`, and `uninstall.php` all read from it, so the three can no longer drift apart.
- **`.distignore`** — the WordPress.org SVN deploy (10up action) reads it as its primary exclude source; without it the SVN checkout would ship `tests/`, `phpstan.neon`, `CLAUDE.md`, etc. Mirrors the existing `.gitattributes` `export-ignore` rules.
- **Plugin Check in CI** (`wordpress/plugin-check-action`) — the same checks the directory runs on submission, so a generic-prefix / escaping / header regression fails the PR instead of the review queue.

### Fixed
- **Uninstall left settings behind.** `uninstall.php` hardcoded 19 option names and silently omitted `appin_chat_auto_open` and `appin_chat_auto_open_delay` (added in 1.1.0), so those two rows survived plugin deletion forever. It now deletes every current option, the migration marker, and every legacy `appin_chat_*` row.
- **Release workflows injected the version into a constant that no longer exists.** `release.yml` and `deploy-wordpress-org.yml` `sed`-ed `APPIN_CHAT_VERSION` in `appin-chat.php`; they now target `public const VERSION` in `src/Plugin.php`.
- **`uninstall.php` only purged the current site on multisite.** Every option is written per-site and `Migration` runs in each blog of a network-activated multisite, so deleting the plugin left rows behind in every blog but one. It now loops `get_sites()` + `switch_to_blog()`, the same shape as appin-search.
- **The migration marker stored the plugin version.** Gating on `Plugin::VERSION` — which CI rewrites on every tag — would have re-run the whole rename on 1.3.1, 1.4.0 and every release after. The marker now stores `Migration::SCHEMA`, a schema version bumped only when a migration is added.
- **CI composer cache was actively harmful.** The key hashed `composer.lock`, which is gitignored (a constant empty hash — the key never changed). Keying on `composer.json` instead would restore a stale `vendor/` under an unchanged key, since it holds constraints, not pins. The cache step is removed.
- **Plugin Check scanned the raw checkout.** It now runs against a `git archive` dist tree — the artifact users actually receive — instead of a working copy full of `.git`, `tests/`, `vendor/` and dev configs.
- **`deploy-wordpress-org.yml` could publish a branch name as the version.** The workflow is `workflow_dispatch`-only, so `GITHUB_REF_NAME` is a branch unless a tag is dispatched; the version `sed`s would then have written the literal `main` into `Plugin::VERSION` and readme.txt's `Stable tag`. It now hard-fails unless the ref is a `vX.Y.Z` tag.
- **`.distignore` would have published the entire git history.** Its presence switches 10up's deploy action from `git archive` (which cannot emit `.git`) to `rsync` of the raw workspace, where `.git` is a real directory and is not excluded for free. `/.git` is now the first entry — without it, the WordPress.org zip would have carried the full repository.

## [1.2.1] - 2026-07-02

### Fixed
- **Distribution zip: exclude internal development documentation.** Added a missing `export-ignore` rule to `.gitattributes` so internal dev docs no longer ship in the plugin package.

## [1.2.0] - 2026-07-02

### Changed
- **WordPress.org contributor: `appin` → `appinio`.** Updated `Contributors:` in `readme.txt` and `Author:` in the plugin header to match the wordpress.org username used for the directory submission.
- **`readme.txt` changelog synced.** Added the missing 1.1.1–1.1.4 entries to the `== Changelog ==` section (it stopped at 1.1.0 while releases reached v1.1.4).

## [1.1.4] - 2026-06-30

### Fixed
- **Release workflow: harden the Slack notification payload.** Switched the `Notify Slack` step from a raw-YAML payload to JSON with `toJSON()` for the release body, so quotes / colons / newlines in release notes can no longer break the parser (v1.1.2's notification failed for exactly this reason).

## [1.1.3] - 2026-06-30

### Fixed
- **Plugin header: remove duplicate `Author URI`.** The WordPress.org directory review rejects identical `Plugin URI` and `Author URI`. Kept `Plugin URI: https://app-in.io` and dropped the redundant `Author URI`.

## [1.1.2] - 2026-06-30

### Fixed
- **`readme.txt`: bump "Tested up to" to 7.0.** Plugin Check flagged `Tested up to: 6.9 < 7.0`; the WordPress.org directory requires this to match the current WordPress version or the plugin is hidden from search.

## [1.1.1] - 2026-06-30

### Changed
- **Release workflow: distribute via R2 + Slack notification.** `release.yml` now uploads `appin-chat.zip` to R2 at the stable public path `https://cdn.app-in.io/plugins/appin-chat.zip` (cache-control `max-age=300`) and posts a Slack notification on success/failure. New required secrets: `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `SLACK_WEBHOOK_URL`.
- **WordPress.org deploy temporarily disabled.** `deploy-wordpress-org.yml` trigger changed from `release: [published]` to `workflow_dispatch` (manual only) until the plugin is approved on the wordpress.org directory.

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
