<?php

declare(strict_types=1);

namespace Appinio\Chat\Tests;

use Appinio\Chat\Plugin;
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
        // Plugin::file() stands in for a plugin-file constant and is what plugins_url()
        // resolves the admin script against — if boot() failed to record it, the
        // settings page would enqueue from the site root.
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);

        $file = dirname(__DIR__).'/appinio-chat.php';

        Plugin::instance()->boot($file);

        self::assertSame($file, Plugin::instance()->file());
    }

    public function test_version_is_a_semver_string(): void
    {
        // CI seds this constant on every tag; a non-semver value here means the
        // release workflow's regex would silently not match.
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[A-Za-z0-9.]+)?$/', Plugin::VERSION);
    }
}
