<?php

declare(strict_types=1);

namespace AppInIo\Chat;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Single source of truth for every option this plugin owns.
 *
 * SettingsPage registers them, Migration renames the pre-1.3.0 ones,
 * and uninstall.php deletes both generations. Keeping the list here is
 * what stops the three from drifting apart (before 1.3.0 uninstall.php
 * silently leaked the two auto-open options added in 1.1.0).
 */
final class Options
{
    public const PREFIX = 'appinio_chat_';

    /**
     * Pre-1.3.0 prefix. Rejected by the WordPress.org review as too generic
     * ("app" is a common word); kept only so Migration and uninstall.php can
     * find and remove the old rows.
     */
    public const LEGACY_PREFIX = 'appin_chat_';

    /** Stores the version whose migrations have already run. */
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
     * The pre-1.3.0 name of a current option key.
     */
    public static function legacy(string $key): string
    {
        return self::LEGACY_PREFIX.substr($key, \strlen(self::PREFIX));
    }

    /**
     * Every option row the plugin may have written, both generations, plus the
     * migration marker — i.e. exactly what uninstall must delete.
     *
     * @return list<string>
     */
    public static function allIncludingLegacy(): array
    {
        $keys = [...self::ALL, self::VERSION];

        foreach (self::ALL as $key) {
            $keys[] = self::legacy($key);
        }

        return $keys;
    }
}
