<?php

declare(strict_types=1);

namespace AppIn\Chat\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

final class ChatWidget
{
    /** @var array<string, string> Map of option keys to HTML attributes */
    private const ATTRIBUTE_MAP = [
        'appin_chat_site_id' => 'site-id',
        'appin_chat_title' => 'title',
        'appin_chat_subtitle' => 'subtitle',
        'appin_chat_logo_url' => 'logo-url',
        'appin_chat_theme' => 'theme',
        'appin_chat_position' => 'position',
        'appin_chat_accent_color' => 'accent-color',
        'appin_chat_price_prefix' => 'price-prefix',
    ];

    /** @var list<string> Options that should be translated via Polylang */
    private const TRANSLATABLE_OPTIONS = [
        'appin_chat_title',
        'appin_chat_subtitle',
        'appin_chat_price_prefix',
    ];

    /** @var array<string, string> Map of option keys to CSS custom properties */
    private const CSS_VAR_MAP = [
        'appin_chat_color_primary' => '--app-in-primary',
        'appin_chat_color_surface' => '--app-in-surface',
        'appin_chat_color_surface_alt' => '--app-in-surface-alt',
        'appin_chat_color_text' => '--app-in-text',
        'appin_chat_color_text_muted' => '--app-in-text-muted',
        'appin_chat_color_border' => '--app-in-border',
        'appin_chat_color_user_bg' => '--app-in-user-bg',
        'appin_chat_color_assistant_bg' => '--app-in-assistant-bg',
        'appin_chat_font' => '--app-in-font',
        'appin_chat_heading_font' => '--app-in-heading-font',
    ];

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

        // The widget script is hosted on AppIn's CDN under a versioned path
        // (/v1/chat.js) with long-lived cache headers set by the CDN.
        // The plugin version is unrelated to the widget build, so passing
        // null prevents WordPress from appending an incorrect ?ver= query.
        // phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script(
            'appin-chat-widget',
            APPIN_CHAT_CDN_URL,
            [],
            null,
            ['strategy' => 'defer', 'in_footer' => true]
        );
        // phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
    }

    public function addModuleType(string $tag, string $handle): string
    {
        if ($handle !== 'appin-chat-widget') {
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
        $manual = get_option('appin_chat_lang', '');

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

            pll_register_string($option, $value, 'AppIn Chat');
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
        return get_option('appin_chat_site_id', '');
    }
}
