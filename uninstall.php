<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$appin_chat_options = [
    'appin_chat_site_id',
    'appin_chat_title',
    'appin_chat_subtitle',
    'appin_chat_logo_url',
    'appin_chat_theme',
    'appin_chat_position',
    'appin_chat_lang',
    'appin_chat_accent_color',
    'appin_chat_price_prefix',
    'appin_chat_color_primary',
    'appin_chat_color_surface',
    'appin_chat_color_surface_alt',
    'appin_chat_color_text',
    'appin_chat_color_text_muted',
    'appin_chat_color_border',
    'appin_chat_color_user_bg',
    'appin_chat_color_assistant_bg',
    'appin_chat_font',
    'appin_chat_heading_font',
];

foreach ($appin_chat_options as $appin_chat_option) {
    delete_option($appin_chat_option);
    delete_site_option($appin_chat_option);
}
