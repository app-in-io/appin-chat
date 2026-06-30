<?php

/**
 * Plugin Name:       AppIn Chat
 * Plugin URI:        https://app-in.io
 * Description:       Add an AI chat widget to your WordPress site. Powered by AppIn.
 * Version:           1.0.0
 * Author:            AppIn
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       appin-chat
 * Domain Path:       /languages
 * Requires at least: 6.3
 * Requires PHP:      8.1
 */

declare(strict_types=1);
use AppIn\Chat\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('APPIN_CHAT_CDN_URL')) {
    define('APPIN_CHAT_CDN_URL', 'https://cdn.app-in.io/v1/chat.js');
}

define('APPIN_CHAT_PLUGIN_FILE', __FILE__);
define('APPIN_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APPIN_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APPIN_CHAT_VERSION', '1.0.0');

require_once __DIR__.'/autoload.php';

add_action('plugins_loaded', function (): void {
    Plugin::instance()->boot();
});
