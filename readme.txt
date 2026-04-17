=== AppIn Chat ===
Contributors: appin
Tags: chat, ai, chatbot, assistant, multilingual
Requires at least: 6.3
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add an AI chat widget to your WordPress site. Powered by AppIn.

== Description ==

AppIn Chat adds a customizable AI assistant widget (`<app-in-chat>` web component) to every page of your WordPress site. The widget answers visitor questions, recommends products, and can be fully themed to match your brand.

The plugin is a thin integration layer: all AI, indexing, and chat logic runs on the AppIn cloud service. You need an AppIn account and a Web Channel ID to use it.

= Features =

* Floating chat widget — light or dark theme, bottom-left or bottom-right position.
* Full visual customization — title, subtitle, logo, accent color, and 8 CSS color variables + body/heading fonts.
* Multilingual — auto-detects language via Polylang, WPML, or the WordPress locale.
* Translatable strings (title, subtitle, price prefix) via Polylang String Translation or WPML.
* Loads as a deferred ES module from the AppIn CDN — zero performance impact on your site.

= Requirements =

* WordPress 6.3+
* PHP 8.1+
* An active AppIn account — [sign up at app-in.io](https://app-in.io).

== External services ==

This plugin relies on the AppIn cloud service to provide the AI chat functionality. It is **not** self-hosted.

**What is sent and when:**

1. On every front-end page load, the plugin outputs a `<script>` tag that loads the AppIn chat widget JavaScript from `https://cdn.app-in.io/v1/chat.js`. The visitor's browser fetches this file directly from AppIn's CDN. AppIn receives standard HTTP request metadata (IP address, user agent, referrer).
2. When a visitor opens the chat and sends a message, the widget sends the message text, the configured Web Channel ID (Site ID), and the session identifier to AppIn's API (`https://api.app-in.io`). AppIn processes the message with an LLM and returns a response.
3. No data is sent to AppIn from the WordPress admin — configuration is stored locally in the site's options table.

**Terms and privacy:**

* Terms of Service: [https://app-in.io/terms](https://app-in.io/terms)
* Privacy Policy: [https://app-in.io/privacy](https://app-in.io/privacy)

Using this plugin means the chat widget script runs in visitors' browsers and end-user chat messages are transmitted to AppIn. Please update your own site's privacy notice accordingly.

== Installation ==

1. Upload the plugin zip via **Plugins → Add New → Upload Plugin**, or install from the WordPress.org plugin directory.
2. Activate the plugin.
3. Go to **Settings → AppIn Chat**.
4. Enter your AppIn **Web Channel ID** (Site ID) and save.
5. Optionally customize title, subtitle, logo, theme, position, colors, and fonts.

The widget appears on the front end as soon as a Site ID is set.

== Support ==

* Bug reports and feature requests: [github.com/app-in-io/appin-chat/issues](https://github.com/app-in-io/appin-chat/issues)
* Account, billing, and widget help: [app-in.io/support](https://app-in.io/support)

== Frequently Asked Questions ==

= Do I need an AppIn account? =

Yes. The plugin is a front-end integration for the AppIn cloud service. Get a free Web Channel ID at [app-in.io](https://app-in.io).

= Does this work without WooCommerce? =

Yes. AppIn Chat has no WooCommerce dependency.

= Can I translate the chat title and subtitle? =

Yes. With Polylang, the plugin auto-registers `appin_chat_title`, `appin_chat_subtitle`, and `appin_chat_price_prefix` via `pll_register_string()` — translate them in **Languages → String Translations**. With WPML, the same strings are registered via `wpml-config.xml` — translate them in **WPML → String Translation**.

= How is the widget language chosen? =

In this order: Polylang current language → WPML current language → manual setting in **Settings → AppIn Chat** → the WordPress site locale (first two characters, e.g. `de_DE` → `de`).

= Can I change the colors? =

Yes. The **Custom Colors** section in **Settings → AppIn Chat** lets you override 8 CSS custom properties (primary, surface, text, border, message backgrounds, etc.) and the body/heading fonts.

= Where is the widget rendered? =

In the site footer (`wp_footer` hook), as an `<app-in-chat>` web component. The widget script is loaded as a deferred ES module from the AppIn CDN.

== Screenshots ==

1. Chat widget open on the front end — header with logo, title, subtitle, and message input.
2. WordPress admin settings page — Site ID, appearance options (title, subtitle, logo, theme, position, language, accent color, price prefix).

== Changelog ==

= 1.0.0 =
* Initial release.
* Settings page with Connection, Appearance, and Custom Colors sections.
* Front-end widget rendering via `<app-in-chat>` web component.
* CDN-loaded chat widget as a deferred ES module.
* Multilingual support: Polylang, WPML, and WordPress locale fallback.
* CSS custom properties for full color and font theming.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
