<?php

declare(strict_types=1);

namespace AppIn\Chat\Admin;

final class SettingsPage
{
    private const OPTION_GROUP = 'appin_chat';

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
        $this->registerColorsSection();
    }

    private function registerConnectionSection(): void
    {
        add_settings_section(
            'appin_chat_connection',
            __('Connection', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Connect your site to the AppIn chat widget.', 'appin-chat')
            ),
            self::SLUG,
        );

        $this->addTextField(
            'appin_chat_site_id',
            __('Site ID', 'appin-chat'),
            'appin_chat_connection',
            __('Web Channel ID from the AppIn dashboard. Required for the widget to work.', 'appin-chat'),
        );

    }

    private function registerAppearanceSection(): void
    {
        add_settings_section(
            'appin_chat_appearance',
            __('Appearance', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Customize the look and feel of the chat widget.', 'appin-chat')
            ),
            self::SLUG,
        );

        $this->addTextField(
            'appin_chat_title',
            __('Title', 'appin-chat'),
            'appin_chat_appearance',
            __('Chat header title. Defaults to "AI Assistant".', 'appin-chat'),
            'AI Assistant',
        );

        $this->addTextField(
            'appin_chat_subtitle',
            __('Subtitle', 'appin-chat'),
            'appin_chat_appearance',
            __('Chat header subtitle. Leave empty to hide.', 'appin-chat'),
        );

        $this->addImageField(
            'appin_chat_logo_url',
            __('Logo', 'appin-chat'),
            'appin_chat_appearance',
            __('Image displayed in the chat header.', 'appin-chat'),
        );

        $this->addSelectField(
            'appin_chat_theme',
            __('Theme', 'appin-chat'),
            'appin_chat_appearance',
            [
                'light' => __('Light', 'appin-chat'),
                'dark' => __('Dark', 'appin-chat'),
            ],
            'light',
        );

        $this->addSelectField(
            'appin_chat_position',
            __('Position', 'appin-chat'),
            'appin_chat_appearance',
            [
                'bottom-right' => __('Bottom Right', 'appin-chat'),
                'bottom-left' => __('Bottom Left', 'appin-chat'),
            ],
            'bottom-right',
        );

        $this->addTextField(
            'appin_chat_lang',
            __('Language', 'appin-chat'),
            'appin_chat_appearance',
            __('Fallback language code (e.g. en, de, fr). Auto-detected from Polylang/WPML when active.', 'appin-chat'),
        );

        $this->addColorField(
            'appin_chat_accent_color',
            __('Accent Color', 'appin-chat'),
            'appin_chat_appearance',
            __('Primary accent color for buttons and highlights.', 'appin-chat'),
        );

        $this->addTextField(
            'appin_chat_price_prefix',
            __('Price Prefix', 'appin-chat'),
            'appin_chat_appearance',
            __('Prefix for price display in product cards (e.g. "from").', 'appin-chat'),
        );
    }

    private function registerColorsSection(): void
    {
        add_settings_section(
            'appin_chat_colors',
            __('Custom Colors', 'appin-chat'),
            fn () => printf(
                '<p>%s</p>',
                esc_html__('Override individual CSS color variables. Leave empty to use defaults.', 'appin-chat')
            ),
            self::SLUG,
        );

        $colors = [
            'appin_chat_color_primary' => __('Primary', 'appin-chat'),
            'appin_chat_color_surface' => __('Surface', 'appin-chat'),
            'appin_chat_color_surface_alt' => __('Surface Alt', 'appin-chat'),
            'appin_chat_color_text' => __('Text', 'appin-chat'),
            'appin_chat_color_text_muted' => __('Text Muted', 'appin-chat'),
            'appin_chat_color_border' => __('Border', 'appin-chat'),
            'appin_chat_color_user_bg' => __('User Message BG', 'appin-chat'),
            'appin_chat_color_assistant_bg' => __('Assistant Message BG', 'appin-chat'),
        ];

        foreach ($colors as $key => $label) {
            $this->addColorField($key, $label, 'appin_chat_colors');
        }

        $this->addTextField(
            'appin_chat_font',
            __('Body Font', 'appin-chat'),
            'appin_chat_colors',
            __('CSS font-family for body text (e.g. Inter, system-ui, sans-serif).', 'appin-chat'),
        );

        $this->addTextField(
            'appin_chat_heading_font',
            __('Heading Font', 'appin-chat'),
            'appin_chat_colors',
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
    ): void {
        register_setting(self::OPTION_GROUP, $key, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '',
        ]);

        add_settings_field(
            $key,
            $label,
            function () use ($key, $description): void {
                $value = get_option($key, '');
                printf(
                    '<input type="color" name="%s" value="%s" style="width:60px;height:34px;padding:2px;" />',
                    esc_attr($key),
                    esc_attr($value),
                );
                printf(
                    ' <input type="text" data-color-text="%s" value="%s" class="small-text" placeholder="#000000" style="width:80px;" />',
                    esc_attr($key),
                    esc_attr($value),
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
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_GROUP);
        do_settings_sections(self::SLUG);
        submit_button();
        echo '</form>';

        $this->renderColorSyncScript();
        $this->renderMediaPickerScript();

        echo '</div>';
    }

    private function renderColorSyncScript(): void
    {
        ?>
        <script>
        document.querySelectorAll('input[type="color"]').forEach(function(picker) {
            var textInput = picker.nextElementSibling;
            if (!textInput || !textInput.dataset.colorText) return;

            picker.addEventListener('input', function() {
                textInput.value = picker.value;
            });
            textInput.addEventListener('input', function() {
                if (/^#[0-9a-fA-F]{6}$/.test(textInput.value)) {
                    picker.value = textInput.value;
                }
            });
        });
        </script>
        <?php
    }

    private function renderMediaPickerScript(): void
    {
        ?>
        <script>
        document.querySelectorAll('.appin-upload-image').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var target = btn.dataset.target;
                var frame = wp.media({ multiple: false, library: { type: 'image' } });
                frame.on('select', function() {
                    var url = frame.state().get('selection').first().toJSON().url;
                    document.getElementById(target).value = url;
                    var preview = document.getElementById(target + '-preview');
                    preview.innerHTML = '<img src="' + url + '" style="max-width:200px;max-height:80px;display:block;" />';
                    // Show remove button if hidden
                    var removeBtn = btn.nextElementSibling;
                    if (removeBtn && removeBtn.classList.contains('appin-remove-image')) {
                        removeBtn.style.display = '';
                    } else {
                        var rm = document.createElement('button');
                        rm.type = 'button';
                        rm.className = 'button appin-remove-image';
                        rm.dataset.target = target;
                        rm.textContent = '<?php echo esc_js(__('Remove', 'appin-chat')); ?>';
                        btn.after(rm);
                        bindRemove(rm);
                    }
                });
                frame.open();
            });
        });
        function bindRemove(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var target = btn.dataset.target;
                document.getElementById(target).value = '';
                document.getElementById(target + '-preview').innerHTML = '';
                btn.remove();
            });
        }
        document.querySelectorAll('.appin-remove-image').forEach(bindRemove);
        </script>
        <?php
    }
}
