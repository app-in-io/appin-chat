# appin-chat

WordPress plugin: AI chat widget (`<app-in-chat>` web component) powered by the AppIn cloud service. PHP 8.1+ | PSR-4 | PHPUnit 11 + Brain Monkey.

> **Layout:** This plugin lives at `wordpress-plugin/appin-chat/` as a subdirectory of the main API project. It has its own `.git`, CI/CD, and GitHub repo (`app-in-io/appin-chat`). The parent API repo ignores this directory.

## CRITICAL RULES

**DO NOT** commit or push without explicit permission from the user.

**DO NOT** push directly to `main`. All work goes through feature branches → PR (squash merge) → merge.

**ALWAYS** update `CHANGELOG.md` when making changes (under `[Unreleased]`).

**ALWAYS** sync `readme.txt` `== Changelog ==` on every release. The release workflows inject the version into `Stable tag`, but nothing updates the readme changelog automatically — add a `= X.Y.Z =` entry (short, user-facing wording) alongside the `CHANGELOG.md` version section before tagging. `CHANGELOG.md` keeps the full technical detail; `readme.txt` gets the concise user-visible summary.

**This plugin MUST work without Composer autoload in production** — the manual `autoload.php` handles PSR-4 loading (`AppIn\Chat\` → `src/`). Composer is only for dev dependencies.

## Commands

```bash
composer install        # dev dependencies
composer test           # phpunit
composer lint           # pint --test
composer lint:fix       # pint
composer analyse        # phpstan (--memory-limit=512M required)
composer ci             # lint + analyse + test (run before push)
```

## Release

1. Move `[Unreleased]` entries in `CHANGELOG.md` to a `## [X.Y.Z] - YYYY-MM-DD` section
2. Add matching `= X.Y.Z =` entry to `readme.txt` `== Changelog ==`
3. PR → squash merge to `main` → wait for CI
4. `gh release create vX.Y.Z` — triggers `release.yml`: injects version into `appin-chat.php` + `readme.txt` `Stable tag`, builds zip, uploads to R2 (`cdn.app-in.io/plugins/appin-chat.zip`), notifies Slack
5. `deploy-wordpress-org.yml` (SVN deploy) is manual-only until the plugin is approved on WordPress.org

## CI

- **test.yml**: PHP 8.2–8.4 matrix on PRs (runtime supports 8.1+, but PHPUnit 11.5+ needs 8.2+)
- **release.yml**: on release published — version injection, zip, R2 upload, Slack
- **deploy-wordpress-org.yml**: `workflow_dispatch` only — WordPress.org SVN deploy via 10up action, assets from `.wordpress-org/`

## WordPress.org

- Slug: `appin-chat`, Contributors: `appinio`
- `readme.txt` must keep: valid `Stable tag` placeholder, `Tested up to` matching current WP version, `== External services ==` disclosure (api.app-in.io + cdn.app-in.io)
- Directory assets (banners, icons, screenshots) live in `.wordpress-org/`
