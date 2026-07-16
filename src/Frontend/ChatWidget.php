<?php

declare(strict_types=1);

namespace Appinio\Chat\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

final class ChatWidget
{
    private const DEFAULT_CDN_URL = 'https://cdn.app-in.io/v1/chat.js';

    /**
     * Script handle. Must stay in one place: enqueueAssets() registers it and
     * addModuleType() matches on it — two literals would silently drift apart
     * and the widget would lose its type="module".
     */
    private const HANDLE = 'appinio-chat-widget';

    /** @var array<string, string> Map of option keys to HTML attributes */
    private const ATTRIBUTE_MAP = [
        'appinio_chat_site_id' => 'site-id',
        'appinio_chat_title' => 'title',
        'appinio_chat_subtitle' => 'subtitle',
        'appinio_chat_logo_url' => 'logo-url',
        'appinio_chat_theme' => 'theme',
        'appinio_chat_position' => 'position',
        'appinio_chat_accent_color' => 'accent-color',
        'appinio_chat_price_prefix' => 'price-prefix',
    ];

    /** @var list<string> Options that should be translated via Polylang */
    private const TRANSLATABLE_OPTIONS = [
        'appinio_chat_title',
        'appinio_chat_subtitle',
        'appinio_chat_price_prefix',
    ];

    /** @var array<string, string> Map of option keys to CSS custom properties */
    private const CSS_VAR_MAP = [
        'appinio_chat_color_primary' => '--app-in-primary',
        'appinio_chat_color_surface' => '--app-in-surface',
        'appinio_chat_color_surface_alt' => '--app-in-surface-alt',
        'appinio_chat_color_text' => '--app-in-text',
        'appinio_chat_color_text_muted' => '--app-in-text-muted',
        'appinio_chat_color_border' => '--app-in-border',
        'appinio_chat_color_user_bg' => '--app-in-user-bg',
        'appinio_chat_color_assistant_bg' => '--app-in-assistant-bg',
        'appinio_chat_font' => '--app-in-font',
        'appinio_chat_heading_font' => '--app-in-heading-font',
    ];

    /**
     * Resolve the chat widget script URL.
     *
     * The production default is baked in as DEFAULT_CDN_URL and passed through the
     * `appinio_chat_cdn_url` filter — the sole override seam (used by the dev harness
     * to target the local Vite dev server). This is a full script URL, not a base.
     *
     * The plugin defines no global constant for it: the WordPress.org review rejects
     * globals defined on a generic prefix, and a filter is the WordPress-native seam
     * anyway. This mirrors SearchWidget::cdnUrl() in appin-search.
     */
    public static function cdnUrl(): string
    {
        return (string) apply_filters('appinio_chat_cdn_url', self::DEFAULT_CDN_URL);
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 2);
        add_action('wp_footer', [$this, 'renderElement']);
    }

    public function enqueueAssets(): void
    {
        if (empty($this->getSiteId())) {
            return;
        }

        // The widget script is hosted on Appinio's CDN under a versioned path
        // (/v1/chat.js) with long-lived cache headers set by the CDN.
        // The plugin version is unrelated to the widget build, so passing
        // null prevents WordPress from appending an incorrect ?ver= query.
        // phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script(
            self::HANDLE,
            self::cdnUrl(),
            [],
            null,
            ['strategy' => 'defer', 'in_footer' => true]
        );
        // phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
    }

    public function addModuleType(string $tag, string $handle): string
    {
        if ($handle !== self::HANDLE) {
            return $tag;
        }

        return str_replace('<script ', '<script type="module" ', $tag);
    }

    public function renderElement(): void
    {
        $siteId = $this->getSiteId();

        if (empty($siteId)) {
            return;
        }

        $attrs = $this->buildAttributes();
        $style = $this->buildStyleAttribute();

        // $attrs and $style are built internally and every value inside them
        // is individually escaped via esc_attr() — safe to output as-is.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        printf('<app-in-chat %s%s></app-in-chat>', $attrs, $style);
    }

    public function buildAttributes(): string
    {
        $parts = [];

        foreach (self::ATTRIBUTE_MAP as $option => $attribute) {
            $value = $this->getOption($option);

            if ($value === '') {
                continue;
            }

            $parts[] = sprintf('%s="%s"', $attribute, esc_attr($value));
        }

        // Lang is resolved dynamically, not from a static option
        $lang = $this->resolveLang();

        if ($lang !== '') {
            $parts[] = sprintf('lang="%s"', esc_attr($lang));
        }

        // Auto-open is emitted only when enabled (not the "never" default), so the
        // attribute does not appear on every page; the delay only when auto-open is on.
        $autoOpen = get_option('appinio_chat_auto_open', 'never');

        if (\is_string($autoOpen) && $autoOpen !== '' && $autoOpen !== 'never') {
            $parts[] = sprintf('auto-open="%s"', esc_attr($autoOpen));

            $delay = (string) get_option('appinio_chat_auto_open_delay', '');

            if ($delay !== '') {
                $parts[] = sprintf('auto-open-delay="%s"', esc_attr($delay));
            }
        }

        return implode(' ', $parts);
    }

    public function buildStyleAttribute(): string
    {
        $vars = [];

        foreach (self::CSS_VAR_MAP as $option => $cssVar) {
            $value = get_option($option, '');

            if ($value === '') {
                continue;
            }

            $vars[] = sprintf('%s: %s', $cssVar, esc_attr($value));
        }

        if ($vars === []) {
            return '';
        }

        return sprintf(' style="%s"', implode('; ', $vars));
    }

    /**
     * Resolve current language: Polylang → WPML → manual setting → WP locale.
     */
    public function resolveLang(): string
    {
        // Polylang
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');

            if (is_string($lang) && $lang !== '') {
                return $lang;
            }
        }

        // WPML — third-party filter from WPML plugin, not our own.
        // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        if (has_filter('wpml_current_language')) {
            $lang = apply_filters('wpml_current_language', null);
            // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

            if (is_string($lang) && $lang !== '' && $lang !== 'all') {
                return $lang;
            }
        }

        // Manual setting
        $manual = get_option('appinio_chat_lang', '');

        if ($manual !== '') {
            return $manual;
        }

        // WordPress locale (de_DE → de)
        $locale = get_locale();

        return substr($locale, 0, 2);
    }

    /**
     * Register translatable strings with Polylang.
     * Called on `init` from Plugin::boot().
     */
    public static function registerPolylangStrings(): void
    {
        if (! function_exists('pll_register_string')) {
            return;
        }

        foreach (self::TRANSLATABLE_OPTIONS as $option) {
            $value = get_option($option, '');

            if ($value === '') {
                continue;
            }

            pll_register_string($option, $value, 'Appinio Chat');
        }
    }

    /**
     * Get option value, applying Polylang translation for translatable strings.
     * WPML translates automatically via wpml-config.xml.
     */
    private function getOption(string $key): string
    {
        $value = get_option($key, '');

        if ($value === '') {
            return '';
        }

        if (function_exists('pll__') && \in_array($key, self::TRANSLATABLE_OPTIONS, true)) {
            return pll__($value);
        }

        return $value;
    }

    private function getSiteId(): string
    {
        return get_option('appinio_chat_site_id', '');
    }
}
