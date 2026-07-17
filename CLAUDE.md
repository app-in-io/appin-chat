# appin-chat

WordPress plugin: AI chat widget (`<app-in-chat>` web component) powered by the Appinio cloud service. PHP 8.1+ | PSR-4 | PHPUnit 11 + Brain Monkey.

> **Naming:** the plugin ships as **Appinio Chat** (slug `appinio-chat`). The git repo and this directory keep the older `appin-chat` name — that is deliberate, it is dev-internal and invisible to the WordPress.org review.

> **Layout:** This plugin lives at `wordpress-plugin/appin-chat/` as a subdirectory of the main API project. It has its own `.git`, CI/CD, and GitHub repo (`app-in-io/appin-chat`). The parent API repo ignores this directory.

## CRITICAL RULES

**DO NOT** commit or push without explicit permission from the user.

**DO NOT** push directly to `main`. All work goes through feature branches → PR (squash merge) → merge.

**ALWAYS** update `CHANGELOG.md` when making changes (under `[Unreleased]`).

**ALWAYS** sync `readme.txt` `== Changelog ==` on every release. The release workflows inject the version into `Stable tag`, but nothing updates the readme changelog automatically — add a `= X.Y.Z =` entry (short, user-facing wording) alongside the `CHANGELOG.md` version section before tagging. `CHANGELOG.md` keeps the full technical detail; `readme.txt` gets the concise user-visible summary.

**This plugin MUST work without Composer autoload in production** — the manual `autoload.php` handles PSR-4 loading (`Appinio\Chat\` → `src/`). Composer is only for dev dependencies.

**One prefix, everywhere: `appinio`** (WordPress.org requirement — the directory pended the plugin three times over this). Namespace `Appinio\Chat`, options/hooks/filters `appinio_chat_*`, JS global `AppinioChatSettings`, plugin slug / text domain / menu slug / script handles `appinio-chat*`, display name "Appinio Chat".

Why so absolute: the review team's tooling **counts distinct prefixes** rather than checking uniqueness, and it splits CamelCase on capitals — `AppInIo` read as `app_in_io`, which also tripped their "common word `app`" rule. The earlier defence "a plugin's own slug is always an acceptable prefix" is a real rule from their handbook, was argued in our 13 Jul reply, and was ignored: the same lines came back in the next review. Do not reintroduce a second prefix, and do not reintroduce a capital inside `Appinio`.

**The plugin defines no global constants** — the widget CDN URL resolves through the `appinio_chat_cdn_url` filter (default in `Frontend\ChatWidget::cdnUrl()`), the plugin path through `Plugin::file()`, the version through `Plugin::VERSION`. Do not reintroduce a `define()`.

**Slug-derived strings must never be literals.** WordPress builds the settings-page hook from the menu slug (`settings_page_{SLUG}`), so `SettingsPage::enqueueMedia()` matches on `'settings_page_'.self::SLUG`, and `ChatWidget::addModuleType()` matches on `self::HANDLE`. Both once hardcoded the slug; a rename would then have silently killed the image picker and the widget's `type="module"` with no error at all.

**Options live in one place**: `src/Options.php`. `SettingsPage` and `uninstall.php` both read from it — adding a setting anywhere else will leak it on uninstall (which is exactly what happened to the 1.1.0 auto-open options).

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
4. `gh release create vX.Y.Z` — triggers `release.yml`: injects version into `appinio-chat.php` + `readme.txt` `Stable tag`, builds zip, uploads to R2 (`cdn.app-in.io/plugins/appinio-chat.zip`), notifies Slack
5. `deploy-wordpress-org.yml` fires on the same release: commits the build to the WordPress.org SVN `trunk`, copies it to `tags/X.Y.Z` and syncs `.wordpress-org/` to the directory assets

To rehearse a deploy without publishing, dispatch `deploy-wordpress-org.yml` **from a version tag** with `dry_run: true` — it checks out SVN and builds trunk but commits nothing. Dispatching from a branch is rejected by the version gate. Re-running a tag whose `tags/X.Y.Z` already exists in SVN is a no-op, so a failed deploy is safe to retry.

## CI

- **test.yml**: PHP 8.2–8.4 matrix on PRs (runtime supports 8.1+, but PHPUnit 11.5+ needs 8.2+)
- **release.yml**: on release published — version injection, zip, R2 upload, Slack
- **deploy-wordpress-org.yml**: on release published (plus `workflow_dispatch` for re-runs and dry runs) — WordPress.org SVN deploy via 10up action, slug `appinio-chat`, assets from `.wordpress-org/`. Needs the `WP_ORG_USERNAME` / `WP_ORG_PASSWORD` secrets (the password is the wordpress.org **SVN password**, not the account password)

## WordPress.org

- Slug: `appinio-chat`, Contributors: `appinio`
- `readme.txt` must keep: valid `Stable tag` placeholder, `Tested up to` matching current WP version, `== External services ==` disclosure (api.app-in.io + cdn.app-in.io)
- Directory assets (banners, icons, screenshots) live in `.wordpress-org/`

## Knowledge graph (graphify)

This repo has its own knowledge graph in `graphify-out/` (gitignored, rebuilt locally).

- Codebase questions: run `graphify query "<question>"` **before** grepping — it returns a scoped
  subgraph instead of raw text. Also: `graphify path "<A>" "<B>"`, `graphify explain "<concept>"`,
  `graphify affected "<symbol>"`.
- Cross-repo questions (this repo ↔ api ↔ embeddings ↔ widget): use the merged graph —
  `graphify query "<question>" --graph <api-repo>/graphify-out/merged-graph.json`, or
  `make graph-query q="..."` from the api repo. This repo's tag in the merged graph is `wordpress-chat`.
- **Freshness is automatic**: a `graphify watch` daemon rebuilds the graph ~3s after any file save,
  git hooks rebuild it on commit/checkout, and an hourly job refreshes the docs + semantic layer.
  Never hand-rebuild; if in doubt run `make graph-status` in the api repo.
- Never commit `graphify-out/` — it is derived output plus an LLM cache.
