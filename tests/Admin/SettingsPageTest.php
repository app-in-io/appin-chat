<?php

declare(strict_types=1);

namespace AppIn\Chat\Tests\Admin;

use AppIn\Chat\Admin\SettingsPage;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class SettingsPageTest extends TestCase
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

    public function test_register_hooks(): void
    {
        $page = new SettingsPage;
        $page->register();

        self::assertNotFalse(has_action('admin_menu', [$page, 'addMenu']));
        self::assertNotFalse(has_action('admin_init', [$page, 'registerSettings']));
        self::assertNotFalse(has_action('admin_enqueue_scripts', [$page, 'enqueueMedia']));
    }

    public function test_add_menu_registers_options_page(): void
    {
        Functions\when('__')->returnArg();

        Functions\expect('add_options_page')
            ->once()
            ->with(
                'AppIn Chat',
                'AppIn Chat',
                'manage_options',
                'appin-chat',
                \Mockery::type('array'),
            );

        (new SettingsPage)->addMenu();

        self::assertTrue(true);
    }

    public function test_enqueue_media_skipped_on_other_pages(): void
    {
        Functions\expect('wp_enqueue_media')->never();
        Functions\expect('wp_enqueue_script')->never();

        (new SettingsPage)->enqueueMedia('dashboard');

        self::assertTrue(true);
    }

    public function test_enqueue_media_runs_on_settings_page(): void
    {
        Functions\when('__')->returnArg();

        Functions\expect('wp_enqueue_media')->once();
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'appin-chat-settings',
                \Mockery::pattern('#assets/js/settings\.js$#'),
                [],
                APPIN_CHAT_VERSION,
                true,
            );
        Functions\expect('wp_localize_script')
            ->once()
            ->with('appin-chat-settings', 'AppInChatSettings', \Mockery::type('array'));

        (new SettingsPage)->enqueueMedia('settings_page_appin-chat');

        self::assertTrue(true);
    }

    public function test_register_settings_calls_register_and_add_functions(): void
    {
        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('register_setting')->justReturn(true);
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(null);

        (new SettingsPage)->registerSettings();

        self::assertTrue(true);
    }

    public function test_render_requires_manage_options_capability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('get_admin_page_title')->never();

        ob_start();
        (new SettingsPage)->render();
        $output = ob_get_clean();

        self::assertEmpty($output);
    }

    public function test_render_outputs_wrap_and_form(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_admin_page_title')->justReturn('AppIn Chat');
        Functions\when('esc_html')->returnArg();
        Functions\when('settings_fields')->justReturn(null);
        Functions\when('do_settings_sections')->justReturn(null);
        Functions\when('submit_button')->alias(function (): void {
            echo '<button>Save</button>';
        });

        ob_start();
        (new SettingsPage)->render();
        $output = ob_get_clean();

        self::assertStringContainsString('<div class="wrap">', $output);
        self::assertStringContainsString('<form method="post" action="options.php">', $output);
        self::assertStringContainsString('AppIn Chat', $output);
        self::assertStringContainsString('<button>Save</button>', $output);
        self::assertStringContainsString('</form>', $output);
    }
}
