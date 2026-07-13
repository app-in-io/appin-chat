<?php

declare(strict_types=1);

use AppInIo\Chat\Options;

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__.'/autoload.php';

// Both option generations: the current `appinio_chat_*` rows, the migration marker,
// and any pre-1.3.0 `appin_chat_*` rows left behind on an install that was deleted
// before Migration ever ran. Driven off Options so the list cannot drift again —
// before 1.3.0 this file hardcoded 19 keys and silently leaked the two auto-open
// options added in 1.1.0.
//
// Every row is written per-site (update_option), and Migration runs on plugins_loaded
// in each blog of a network-activated multisite, so each blog must be purged
// individually — same shape as appin-search's uninstall.
$appinio_chat_purge = static function (): void {
    foreach (Options::allIncludingLegacy() as $appinio_chat_option) {
        delete_option($appinio_chat_option);
    }
};

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $appinio_chat_site_id) {
        switch_to_blog((int) $appinio_chat_site_id);
        $appinio_chat_purge();
        restore_current_blog();
    }
} else {
    $appinio_chat_purge();
}
