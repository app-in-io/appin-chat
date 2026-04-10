<?php

declare(strict_types=1);

namespace AppIn\Chat;

use AppIn\Chat\Admin\SettingsPage;
use AppIn\Chat\Frontend\ChatWidget;

final class Plugin
{
    private static ?self $instance = null;

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    public function boot(): void
    {
        (new SettingsPage)->register();
        (new ChatWidget)->register();

        add_action('init', [ChatWidget::class, 'registerPolylangStrings']);
    }
}
