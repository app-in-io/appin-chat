<?php

declare(strict_types=1);

namespace Appinio\Chat;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Single source of truth for every option this plugin owns.
 *
 * SettingsPage registers them and uninstall.php deletes them. Keeping the list
 * here is what stops the two from drifting apart (before 1.3.0 uninstall.php
 * silently leaked the two auto-open options added in 1.1.0).
 */
final class Options
{
    public const PREFIX = 'appinio_chat_';

    /**
     * Marker row written by the 1.3.0 option migration, which has since been removed.
     * Nothing writes it any more — it is kept solely so uninstall still clears it from
     * installs that ran 1.3.0. Do not delete the constant: that would strand the row in
     * wp_options forever.
     */
    public const VERSION = self::PREFIX.'version';

    /** @var list<string> Every setting, current prefix. */
    public const ALL = [
        self::PREFIX.'site_id',
        self::PREFIX.'title',
        self::PREFIX.'subtitle',
        self::PREFIX.'logo_url',
        self::PREFIX.'theme',
        self::PREFIX.'position',
        self::PREFIX.'lang',
        self::PREFIX.'accent_color',
        self::PREFIX.'price_prefix',
        self::PREFIX.'auto_open',
        self::PREFIX.'auto_open_delay',
        self::PREFIX.'color_primary',
        self::PREFIX.'color_surface',
        self::PREFIX.'color_surface_alt',
        self::PREFIX.'color_text',
        self::PREFIX.'color_text_muted',
        self::PREFIX.'color_border',
        self::PREFIX.'color_user_bg',
        self::PREFIX.'color_assistant_bg',
        self::PREFIX.'font',
        self::PREFIX.'heading_font',
    ];

    /**
     * Every option row the plugin may have written, plus the leftover 1.3.0 marker —
     * i.e. exactly what uninstall must delete. Deliberately not called all(): one
     * character away from the ALL constant it is built from, and the two differ.
     *
     * @return list<string>
     */
    public static function forUninstall(): array
    {
        return [...self::ALL, self::VERSION];
    }
}
