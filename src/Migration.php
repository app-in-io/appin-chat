<?php

declare(strict_types=1);

namespace AppInIo\Chat;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * One-time data migrations, gated on the version stored in Options::VERSION.
 *
 * 1.3.0 renamed every option from the `appin_chat_` prefix to `appinio_chat_`
 * to satisfy the WordPress.org unique-prefix requirement. Without this, an
 * existing install would silently lose its Site ID and theming on upgrade.
 */
final class Migration
{
    /**
     * Schema version, NOT the plugin version. Bump only when a new migration is
     * added below. Gating on Plugin::VERSION instead would re-run every migration
     * on every release, since CI rewrites Plugin::VERSION on each tag.
     */
    public const SCHEMA = '1.3.0';

    /**
     * Sentinel telling "option row absent" apart from "option row holds an
     * empty string" — several settings legitimately default to ''.
     */
    private const MISSING = "\0appinio_chat_missing\0";

    public static function maybeRun(): void
    {
        if (get_option(Options::VERSION) === self::SCHEMA) {
            return;
        }

        self::renameLegacyOptions();

        update_option(Options::VERSION, self::SCHEMA);
    }

    /**
     * Copy every pre-1.3.0 `appin_chat_*` row to its `appinio_chat_*` name and
     * drop the old one. A value already present under the new name wins — the
     * legacy row is then only deleted, never allowed to overwrite it.
     */
    private static function renameLegacyOptions(): void
    {
        foreach (Options::ALL as $key) {
            $legacyKey = Options::legacy($key);
            $legacyValue = get_option($legacyKey, self::MISSING);

            if ($legacyValue === self::MISSING) {
                continue;
            }

            if (get_option($key, self::MISSING) === self::MISSING) {
                update_option($key, $legacyValue);
            }

            delete_option($legacyKey);
        }
    }
}
