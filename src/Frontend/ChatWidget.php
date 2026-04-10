<?php

declare(strict_types=1);

namespace AppIn\Chat\Frontend;

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
        'appin_chat_lang' => 'lang',
        'appin_chat_accent_color' => 'accent-color',
        'appin_chat_price_prefix' => 'price-prefix',
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

        wp_enqueue_script(
            'appin-chat-widget',
            APPIN_CHAT_CDN_URL,
            [],
            APPIN_CHAT_VERSION,
            ['strategy' => 'defer', 'in_footer' => true]
        );
    }

    public function addModuleType(string $tag, string $handle): string
    {
        if ('appin-chat-widget' !== $handle) {
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

        printf('<app-in-chat %s%s></app-in-chat>', $attrs, $style);
    }

    public function buildAttributes(): string
    {
        $parts = [];

        foreach (self::ATTRIBUTE_MAP as $option => $attribute) {
            $value = get_option($option, '');

            if ($value === '') {
                continue;
            }

            $parts[] = sprintf('%s="%s"', $attribute, esc_attr($value));
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

    private function getSiteId(): string
    {
        return get_option('appin_chat_site_id', '');
    }
}
