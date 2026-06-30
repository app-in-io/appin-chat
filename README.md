# AppIn Chat for WordPress

Thin WordPress plugin that adds the [AppIn AI](https://app-in.io) chat widget to any WordPress site. No WooCommerce required.

This repository is the **plugin shell** — the actual chat widget JavaScript is loaded from CDN (`https://cdn.app-in.io/v1/chat.js`) and lives in the separate `widget/` repo.

> For end-user documentation, install instructions, and the list of features, see [`readme.txt`](./readme.txt) (the wordpress.org-format readme).

## Requirements

- WordPress 6.3+
- PHP 8.1+

## Repository layout

```
appin-chat.php              Bootstrap: header, constants, autoloader wiring
autoload.php                PSR-4 autoloader (no Composer needed at runtime)
uninstall.php               Removes all plugin options on plugin deletion
readme.txt                  wordpress.org readme (Stable tag, FAQ, screenshots)
README.md                   This file (for developers)
LICENSE                     GPL-2.0
CHANGELOG.md                Per-version changes (developer-facing)
wpml-config.xml             WPML String Translation config
pint.json                   Laravel Pint (code style) config
phpstan.neon                PHPStan (static analysis) config, level 5
phpunit.xml                 PHPUnit config
composer.json               Dev dependencies + scripts (test, lint, analyse)

src/
  Plugin.php                Singleton, boot sequence
  Admin/SettingsPage.php    Settings page (Connection, Appearance, Custom Colors)
  Frontend/ChatWidget.php   Script enqueue + <app-in-chat> rendering

assets/
  js/settings.js            Admin-side JS (color sync, media picker)

languages/                  Translation files (.pot/.po/.mo)

tests/
  bootstrap.php             Brain Monkey + WordPress stubs
  Admin/SettingsPageTest.php
  Frontend/ChatWidgetTest.php

.wordpress-org/             Assets for wordpress.org SVN (icons, banners, screenshots)
                            Excluded from plugin distribution zip
.github/workflows/
  test.yml                  PHPUnit + Pint + PHPStan on PHP 8.1–8.4
  release.yml               Build zip on GitHub Release: attach to release, upload to R2, Slack notify
  deploy-wordpress-org.yml  Push to wordpress.org SVN (temporarily disabled — manual dispatch only)
```

## Local development

Install dev dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test           # all tests
composer test:filter X  # filter by name
```

Lint + static analysis:

```bash
composer lint           # check style (pint --test)
composer lint:fix       # auto-fix (pint)
composer analyse        # phpstan level 5
composer ci             # lint + analyse + test
```

### Testing against a real WordPress install

```bash
# clone into your WP plugins directory
cd wp-content/plugins
git clone https://github.com/app-in-io/appin-chat.git
cd appin-chat
composer install --no-dev
# activate in WP Admin, configure at Settings → AppIn Chat
```

## CI/CD

- **On PR / push to main** (`test.yml`): PHPUnit + Pint + PHPStan on PHP 8.1, 8.2, 8.3, 8.4.
- **On GitHub Release** (`release.yml`): builds `appin-chat.zip` with the tag version substituted into `appin-chat.php` and `readme.txt`, attaches it to the release, uploads it to R2 at the stable public path `https://cdn.app-in.io/plugins/appin-chat.zip`, and posts a Slack notification. Requires `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, and `SLACK_WEBHOOK_URL` secrets.
- **`deploy-wordpress-org.yml`** (temporarily disabled): would push the build to wordpress.org SVN (`trunk/` + `tags/X.Y.Z/`) and upload `.wordpress-org/` to SVN `assets/`. Disabled because the plugin is not yet approved on the wordpress.org directory; trigger is `workflow_dispatch` only. Re-enable by restoring the `release: [published]` trigger once approved (requires `WP_ORG_USERNAME` / `WP_ORG_PASSWORD`).

## Cutting a release

1. Update `[Unreleased]` → `[X.Y.Z] - YYYY-MM-DD` in `CHANGELOG.md`.
2. Update the `== Changelog ==` section in `readme.txt` with a user-facing summary.
3. Commit.
4. Tag: `git tag vX.Y.Z && git push --tags`.
5. Create a GitHub Release from the tag. `release.yml` fires automatically (zip → GitHub Release + R2 + Slack). The wordpress.org deploy is currently disabled.

Version strings in `appin-chat.php` (`Version:` header, `APPIN_CHAT_VERSION` constant) and `readme.txt` (`Stable tag:`) are substituted by CI — you do not edit them manually.

## Architecture notes

- **Namespace**: `AppIn\Chat`
- **No Composer at runtime**: a hand-rolled PSR-4 autoloader in `autoload.php` resolves classes from `src/`. Keeps the zip small and avoids shipping `vendor/`.
- **Widget script**: enqueued from a fixed CDN path (`/v1/chat.js`). The plugin passes `null` as the version to `wp_enqueue_script` — the JS build is versioned at the path level and cached by CDN headers; the plugin version is unrelated.
- **Options prefix**: all settings are stored as `appin_chat_*` options.
- **i18n**: text domain `appin-chat`. No `load_plugin_textdomain()` call — wordpress.org auto-loads translations from the WP translate server for plugins hosted there.

## Releases to wordpress.org

Asset files in `.wordpress-org/`:

- `icon-256x256.png`, `icon-128x128.png` — plugin icon
- `banner-1544x500.png`, `banner-772x250.png` — plugin page banner
- `screenshot-1.png`, `screenshot-2.png` — screenshots, numbered to match descriptions in `readme.txt`

These files go to the wordpress.org SVN `assets/` directory, **not** into the distributed zip (excluded via `.gitattributes` `export-ignore`).

## License

GPL-2.0-or-later — see [`LICENSE`](./LICENSE).
