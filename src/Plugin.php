<?php

declare(strict_types=1);

namespace AppInIo\Chat;

use AppInIo\Chat\Admin\SettingsPage;
use AppInIo\Chat\Frontend\ChatWidget;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public const VERSION = '1.3.0';

    private static ?self $instance = null;

    private string $file = '';

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * @param  string  $file  Absolute path to the main plugin file. Replaces the
     *                        removed APPIN_CHAT_PLUGIN_FILE/_DIR/_URL constants —
     *                        the WordPress.org review rejects globals defined on a
     *                        generic prefix, so the plugin now defines none at all.
     */
    public function boot(string $file): void
    {
        $this->file = $file;

        Migration::maybeRun();

        (new SettingsPage)->register();
        (new ChatWidget)->register();

        add_action('init', [ChatWidget::class, 'registerPolylangStrings']);
    }

    public function file(): string
    {
        return $this->file;
    }
}
