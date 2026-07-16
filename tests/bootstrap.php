<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';

// ABSPATH is the only constant the plugin still reads — it guards every file against
// direct access. The plugin defines none of its own (see appinio-chat.php): the CDN URL
// is a filter, the plugin path is Plugin::file(), the version is Plugin::VERSION.
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
