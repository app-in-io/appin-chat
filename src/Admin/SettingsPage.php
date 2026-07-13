<?php

declare(strict_types=1);

namespace AppInIo\Chat\Admin;

use AppInIo\Chat\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

final class SettingsPage
{
    private const OPTION_GROUP = 'appinio_chat';

    /**
     * Menu slug and script handle. Stays `appin-chat`: it is the plugin's own
     * WordPress.org slug and text domain, which is always an acceptable prefix.
     */
    private const SLUG = 'appin-chat';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueMedia']);
    }

    public function enqueueMedia(string $hook): void
    {
        if ($hook !== 'settings_page_appin-chat') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'appin-chat-settings',
            plugins_url('assets/js/settings.js', Plugin::instance()->file()),
            [],
            Plugin::VERSION,
            true,
        );

        wp_localize_script('appin-chat-settings', 'AppInIoChatSettings', [
            'i18n' => [
                'remove' => __('Remove', 'appin-chat'),
            ],
        ]);
    }

    public function addMenu(): void
    {
        add_options_page(
            __('AppIn Chat', 'appin-chat'),
            __('AppIn Chat', 'appin-chat'),
            'manage_options',
            self::SLUG,
            [$this, 'render'],
        );
    }

    public function registerSettings(): void
    {
        $this->registerConnectionSection();
        $this->registerAppearanceSection();
        $this->registerBehaviorSection();
        $this->registerColorsSection();
    }

    private function registerConnectionSection(): void
    {
        add_settings_section(
            'appinio_chat_connection',
            __('Connection', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Connect your site to the AppIn chat widget.', 'appin-chat')
            ),
            self::SLUG,
        );

        $this->addTextField(
            'appinio_chat_site_id',
            __('Site ID', 'appin-chat'),
            'appinio_chat_connection',
            __('Web Channel ID from the AppIn dashboard. Required for the widget to work.', 'appin-chat'),
        );

    }

    private function registerAppearanceSection(): void
    {
        add_settings_section(
            'appinio_chat_appearance',
            __('Appearance', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Customize the look and feel of the chat widget.', 'appin-chat')
            ),
            self::SLUG,
        );

        $this->addTextField(
            'appinio_chat_title',
            __('Title', 'appin-chat'),
            'appinio_chat_appearance',
            __('Chat header title. Defaults to "AI Assistant".', 'appin-chat'),
            'AI Assistant',
        );

        $this->addTextField(
            'appinio_chat_subtitle',
            __('Subtitle', 'appin-chat'),
            'appinio_chat_appearance',
            __('Chat header subtitle. Leave empty to hide.', 'appin-chat'),
        );

        $this->addImageField(
            'appinio_chat_logo_url',
            __('Logo', 'appin-chat'),
            'appinio_chat_appearance',
            __('Image displayed in the chat header.', 'appin-chat'),
        );

        $this->addSelectField(
            'appinio_chat_theme',
            __('Theme', 'appin-chat'),
            'appinio_chat_appearance',
            [
                'light' => __('Light', 'appin-chat'),
                'dark' => __('Dark', 'appin-chat'),
            ],
            'light',
        );

        $this->addSelectField(
            'appinio_chat_position',
            __('Position', 'appin-chat'),
            'appinio_chat_appearance',
            [
                'bottom-right' => __('Bottom Right', 'appin-chat'),
                'bottom-left' => __('Bottom Left', 'appin-chat'),
            ],
            'bottom-right',
        );

        $this->addTextField(
            'appinio_chat_lang',
            __('Language', 'appin-chat'),
            'appinio_chat_appearance',
            __('Fallback language code (e.g. en, de, fr). Auto-detected from Polylang/WPML when active.', 'appin-chat'),
        );

        $this->addColorField(
            'appinio_chat_accent_color',
            __('Accent Color', 'appin-chat'),
            'appinio_chat_appearance',
            __('Primary accent color for buttons and highlights.', 'appin-chat'),
        );

        $this->addTextField(
            'appinio_chat_price_prefix',
            __('Price Prefix', 'appin-chat'),
            'appinio_chat_appearance',
            __('Prefix for price display in product cards (e.g. "from").', 'appin-chat'),
        );
    }

    private function registerBehaviorSection(): void
    {
        add_settings_section(
            'appinio_chat_behavior',
            __('Behavior', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Control how the chat window behaves after the page loads.', 'appin-chat')
            ),
            self::SLUG,
        );

        $this->addSelectField(
            'appinio_chat_auto_open',
            __('Auto-open', 'appin-chat'),
            'appinio_chat_behavior',
            [
                'never' => __('Never', 'appin-chat'),
                'once' => __('Once per session', 'appin-chat'),
                'always' => __('Every page load', 'appin-chat'),
            ],
            'never',
        );

        register_setting(self::OPTION_GROUP, 'appinio_chat_auto_open_delay', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5,
        ]);

        add_settings_field(
            'appinio_chat_auto_open_delay',
            __('Auto-open Delay (seconds)', 'appin-chat'),
            function (): void {
                $value = (string) get_option('appinio_chat_auto_open_delay', 5);
                printf(
                    '<input type="number" min="0" name="%s" value="%s" class="small-text" />',
                    esc_attr('appinio_chat_auto_open_delay'),
                    esc_attr($value),
                );
                printf(
                    '<p class="description">%s</p>',
                    esc_html__('Seconds to wait after page load before auto-opening. Used only when Auto-open is enabled.', 'appin-chat')
                );
            },
            self::SLUG,
            'appinio_chat_behavior',
        );
    }

    private function registerColorsSection(): void
    {
        add_settings_section(
            'appinio_chat_colors',
            __('Custom Colors', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Override individual CSS color variables. Leave empty to use defaults.', 'appin-chat')
            ),
            self::SLUG,
        );

        $colors = [
            'appinio_chat_color_primary' => [__('Primary', 'appin-chat'), '#37B7FF'],
            'appinio_chat_color_surface' => [__('Surface', 'appin-chat'), '#FFFFFF'],
            'appinio_chat_color_surface_alt' => [__('Surface Alt', 'appin-chat'), '#F4F4F5'],
            'appinio_chat_color_text' => [__('Text', 'appin-chat'), '#18181B'],
            'appinio_chat_color_text_muted' => [__('Text Muted', 'appin-chat'), '#71717A'],
            'appinio_chat_color_border' => [__('Border', 'appin-chat'), '#E4E4E7'],
            'appinio_chat_color_user_bg' => [__('User Message BG', 'appin-chat'), '#EFF6FF'],
            'appinio_chat_color_assistant_bg' => [__('Assistant Message BG', 'appin-chat'), '#F0FDF4'],
        ];

        foreach ($colors as $key => [$label, $placeholder]) {
            $this->addColorField($key, $label, 'appinio_chat_colors', placeholder: $placeholder);
        }

        $this->addTextField(
            'appinio_chat_font',
            __('Body Font', 'appin-chat'),
            'appinio_chat_colors',
            __('CSS font-family for body text (e.g. Inter, system-ui, sans-serif).', 'appin-chat'),
        );

        $this->addTextField(
            'appinio_chat_heading_font',
            __('Heading Font', 'appin-chat'),
            'appinio_chat_colors',
            __('CSS font-family for headings (e.g. Space Grotesk, system-ui, sans-serif).', 'appin-chat'),
        );
    }

    private function addTextField(
        string $key,
        string $label,
        string $section,
        string $description = '',
        string $placeholder = '',
    ): void {
        register_setting(self::OPTION_GROUP, $key, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        add_settings_field(
            $key,
            $label,
            function () use ($key, $description, $placeholder): void {
                $value = get_option($key, '');
                printf(
                    '<input type="text" name="%s" value="%s" class="regular-text" placeholder="%s" />',
                    esc_attr($key),
                    esc_attr($value),
                    esc_attr($placeholder),
                );
                if ($description !== '') {
                    printf('<p class="description">%s</p>', esc_html($description));
                }
            },
            self::SLUG,
            $section,
        );
    }

    private function addSelectField(
        string $key,
        string $label,
        string $section,
        array $options,
        string $default = '',
    ): void {
        register_setting(self::OPTION_GROUP, $key, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => $default,
        ]);

        add_settings_field(
            $key,
            $label,
            function () use ($key, $options, $default): void {
                $value = get_option($key, $default);
                printf('<select name="%s">', esc_attr($key));
                foreach ($options as $optionValue => $optionLabel) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($optionValue),
                        selected($value, $optionValue, false),
                        esc_html($optionLabel),
                    );
                }
                echo '</select>';
            },
            self::SLUG,
            $section,
        );
    }

    private function addColorField(
        string $key,
        string $label,
        string $section,
        string $description = '',
        string $placeholder = '#000000',
    ): void {
        register_setting(self::OPTION_GROUP, $key, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '',
        ]);

        add_settings_field(
            $key,
            $label,
            function () use ($key, $description, $placeholder): void {
                $value = get_option($key, '');
                $display = $value !== '' ? $value : $placeholder;
                printf(
                    '<input type="color" name="%s" value="%s" data-default="%s" style="width:60px;height:34px;padding:2px;" />',
                    esc_attr($key),
                    esc_attr($display),
                    esc_attr($placeholder),
                );
                printf(
                    ' <input type="text" data-color-text="%s" value="%s" class="small-text" placeholder="%s" style="width:80px;" />',
                    esc_attr($key),
                    esc_attr($value),
                    esc_attr($placeholder),
                );
                if ($description !== '') {
                    printf('<p class="description">%s</p>', esc_html($description));
                }
            },
            self::SLUG,
            $section,
        );
    }

    private function addImageField(
        string $key,
        string $label,
        string $section,
        string $description = '',
    ): void {
        register_setting(self::OPTION_GROUP, $key, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        add_settings_field(
            $key,
            $label,
            function () use ($key, $description): void {
                $value = get_option($key, '');
                printf(
                    '<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
                    esc_attr($key),
                    esc_attr($value),
                );
                printf(
                    '<div id="%s-preview" style="margin-bottom:8px;">',
                    esc_attr($key),
                );
                if ($value !== '') {
                    printf(
                        '<img src="%s" style="max-width:200px;max-height:80px;display:block;" />',
                        esc_url($value),
                    );
                }
                echo '</div>';
                printf(
                    '<button type="button" class="button appin-upload-image" data-target="%s">%s</button>',
                    esc_attr($key),
                    esc_html__('Select Image', 'appin-chat'),
                );
                if ($value !== '') {
                    printf(
                        ' <button type="button" class="button appin-remove-image" data-target="%s">%s</button>',
                        esc_attr($key),
                        esc_html__('Remove', 'appin-chat'),
                    );
                }
                if ($description !== '') {
                    printf('<p class="description">%s</p>', esc_html($description));
                }
            },
            self::SLUG,
            $section,
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>'.esc_html(get_admin_page_title()).'</h1>';

        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_GROUP);
        do_settings_sections(self::SLUG);
        submit_button();
        echo '</form>';

        echo '</div>';
    }
}
