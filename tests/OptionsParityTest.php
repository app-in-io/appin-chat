<?php

declare(strict_types=1);

namespace Appinio\Chat\Tests;

use Appinio\Chat\Admin\SettingsPage;
use Appinio\Chat\Options;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Enforces the invariant the options refactor rests on: the settings SettingsPage
 * registers are exactly the ones Options knows about — which are the ones
 * uninstall.php deletes.
 *
 * Without this test the invariant is only a comment, and the 1.1.0 bug (auto_open /
 * auto_open_delay added to SettingsPage, never added to the uninstall list, left in
 * wp_options forever) reproduces the next time someone adds a setting.
 */
class OptionsParityTest extends TestCase
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

    public function test_settings_page_registers_exactly_the_options_listed_in_options(): void
    {
        $registered = [];

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(null);
        Functions\when('register_setting')->alias(
            function (string $group, string $key) use (&$registered): void {
                $registered[] = $key;
            }
        );

        (new SettingsPage)->registerSettings();

        sort($registered);
        $expected = Options::ALL;
        sort($expected);

        self::assertSame(
            $expected,
            $registered,
            'SettingsPage and Options::ALL disagree — a setting registered here but missing from '
            .'Options will survive uninstall.'
        );
    }

    public function test_every_option_carries_the_appinio_prefix(): void
    {
        foreach (Options::ALL as $key) {
            self::assertStringStartsWith(Options::PREFIX, $key);
        }

        self::assertStringStartsWith(Options::PREFIX, Options::VERSION);
    }

    public function test_uninstall_list_covers_every_option_and_the_marker(): void
    {
        $all = Options::forUninstall();

        self::assertContains('appinio_chat_auto_open', $all);
        self::assertContains('appinio_chat_auto_open_delay', $all);
        self::assertContains(Options::VERSION, $all);
        self::assertCount(\count(Options::ALL) + 1, $all);
    }
}
