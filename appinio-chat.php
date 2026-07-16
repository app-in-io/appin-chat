<?php

/**
 * Plugin Name:       Appinio Chat
 * Plugin URI:        https://app-in.io
 * Description:       Add an AI chat widget to your WordPress site. Powered by Appinio.
 * Version:           1.3.0
 * Author:            appinio
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       appinio-chat
 * Domain Path:       /languages
 * Requires at least: 6.3
 * Requires PHP:      8.1
 */

declare(strict_types=1);
use Appinio\Chat\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

// This plugin defines no global constants. The widget script URL resolves through
// the `appinio_chat_cdn_url` filter (Frontend\ChatWidget::cdnUrl()), the plugin path
// through Plugin::file(), and the version through Plugin::VERSION.
require_once __DIR__.'/autoload.php';

add_action('plugins_loaded', function (): void {
    Plugin::instance()->boot(__FILE__);
});
