<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define WordPress constants used by the plugin
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (! defined('APPIN_CHAT_CDN_URL')) {
    define('APPIN_CHAT_CDN_URL', 'https://cdn.app-in.io/v1/chat.js');
}

if (! defined('APPIN_CHAT_PLUGIN_FILE')) {
    define('APPIN_CHAT_PLUGIN_FILE', dirname(__DIR__) . '/appin-chat.php');
}

if (! defined('APPIN_CHAT_PLUGIN_DIR')) {
    define('APPIN_CHAT_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (! defined('APPIN_CHAT_PLUGIN_URL')) {
    define('APPIN_CHAT_PLUGIN_URL', 'https://example.com/wp-content/plugins/appin-chat/');
}

if (! defined('APPIN_CHAT_VERSION')) {
    define('APPIN_CHAT_VERSION', '1.0.0-test');
}
