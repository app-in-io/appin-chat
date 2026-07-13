<?php

declare(strict_types=1);

namespace AppInIo\Chat\Tests;

use AppInIo\Chat\Migration;
use AppInIo\Chat\Options;
use AppInIo\Chat\Plugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_boot_records_the_plugin_file(): void
    {
        // Plugin::file() replaces the removed APPIN_CHAT_PLUGIN_FILE constant and is
        // what plugins_url() resolves the admin script against — if boot() failed to
        // record it, the settings page would enqueue from the site root.
        Functions\when('get_option')->justReturn(Migration::SCHEMA);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        $file = dirname(__DIR__).'/appin-chat.php';

        Plugin::instance()->boot($file);

        self::assertSame($file, Plugin::instance()->file());
    }

    public function test_version_is_a_semver_string(): void
    {
        // CI seds this constant on every tag; a non-semver value here means the
        // release workflow's regex would silently not match.
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[A-Za-z0-9.]+)?$/', Plugin::VERSION);
    }

    public function test_migration_schema_is_independent_of_the_plugin_version(): void
    {
        // The marker stores the schema version, not Plugin::VERSION — otherwise the
        // migration would re-run on every release, since CI rewrites Plugin::VERSION.
        self::assertSame(Options::PREFIX.'version', Options::VERSION);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Migration::SCHEMA);
    }
}
