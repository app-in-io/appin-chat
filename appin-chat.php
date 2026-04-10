<?php

/**
 * Plugin Name: AppIn Chat
 * Plugin URI:  https://app-in.io
 * Description: Add an AI chat widget to your WordPress site. Powered by AppIn.
 * Version:     0.0.0-dev
 * Author:      AppIn
 * Author URI:  https://app-in.io
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (! \defined('ABSPATH')) {
    exit;
}

if (! \defined('APPIN_CHAT_CDN_URL')) {
    \define('APPIN_CHAT_CDN_URL', 'https://cdn.app-in.io/v1/chat.js');
}

\define('APPIN_CHAT_PLUGIN_FILE', __FILE__);
\define('APPIN_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
\define('APPIN_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
\define('APPIN_CHAT_VERSION', '0.0.0-dev');

require_once __DIR__ . '/autoload.php';

add_action('plugins_loaded', function (): void {
    AppIn\Chat\Plugin::instance()->boot();
});
