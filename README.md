# AppIn Chat for WordPress

Add an [AppIn AI](https://app-in.io) chat widget to any WordPress site. No WooCommerce required.

## Requirements

- WordPress 6.0+
- PHP 8.1+
- AppIn Web Channel ID ([get one here](https://my.app-in.io))

## Installation

1. Download the plugin zip or clone this repo into `wp-content/plugins/appin-chat/`
2. Activate in WordPress Admin > Plugins
3. Go to Settings > AppIn Chat
4. Enter your Web Channel ID (Site ID) and save

## Features

### Chat Widget

Renders the `<app-in-chat>` web component on every page. The widget script is loaded from CDN (`https://cdn.app-in.io/v1/chat.js`) as an ES module.

### Full Customization

All widget properties are configurable from the admin panel:

| Setting | Attribute | Description |
|---|---|---|
| Site ID | `site-id` | Web Channel ID (required) |
| Title | `title` | Chat header title |
| Subtitle | `subtitle` | Chat header subtitle |
| Logo URL | `logo-url` | Header logo image |
| Theme | `theme` | `light` or `dark` |
| Position | `position` | `bottom-right` or `bottom-left` |
| Language | `lang` | Auto-detected or manual fallback |
| Accent Color | `accent-color` | Primary color for buttons |
| Price Prefix | `price-prefix` | Prefix for product card prices |

### Custom Colors (CSS Variables)

Override individual color variables via the admin panel:

| Setting | CSS Variable |
|---|---|
| Primary | `--app-in-primary` |
| Surface | `--app-in-surface` |
| Surface Alt | `--app-in-surface-alt` |
| Text | `--app-in-text` |
| Text Muted | `--app-in-text-muted` |
| Border | `--app-in-border` |
| User Message BG | `--app-in-user-bg` |
| Assistant Message BG | `--app-in-assistant-bg` |
| Body Font | `--app-in-font` |
| Heading Font | `--app-in-heading-font` |

### Multilingual Support (Polylang / WPML)

Language is auto-detected when a translation plugin is active:

1. **Polylang** — `pll_current_language()` per page
2. **WPML** — `wpml_current_language` filter per page
3. **Manual setting** — fallback from admin panel
4. **WordPress locale** — `get_locale()` → first 2 chars

Translatable strings (title, subtitle, price prefix):

- **Polylang** — registered via `pll_register_string()`, translate in Strings Translation
- **WPML** — auto-translated via `wpml-config.xml`, translate in String Translation

### Local Development

Override the CDN URL in `wp-config.php`:

```php
define('APPIN_CHAT_CDN_URL', 'http://localhost:5173/src/chat/index.ts');
```

## Architecture

```
appin-chat.php              Bootstrap, constants
autoload.php                PSR-4 autoloader (no Composer required)
wpml-config.xml             WPML string translation config
src/
  Plugin.php                Singleton, boot sequence
  Admin/SettingsPage.php    Settings page (3 sections: connection, appearance, colors)
  Frontend/ChatWidget.php   CDN script enqueue + <app-in-chat> rendering
```

Namespace: `AppIn\Chat`

## License

GPL-2.0-or-later
