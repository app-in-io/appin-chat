<?php

declare(strict_types=1);

namespace Appinio\Chat\Tests\Admin;

use Appinio\Chat\Admin\SettingsPage;
use Appinio\Chat\Options;
use Appinio\Chat\Plugin;
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
                'Appinio Chat',
                'Appinio Chat',
                'manage_options',
                'appinio-chat',
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
        Functions\when('plugins_url')->alias(
            fn (string $path, string $pluginFile): string => 'https://example.com/wp-content/plugins/appinio-chat/'.$path
        );

        Functions\expect('wp_enqueue_media')->once();
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'appinio-chat-settings',
                \Mockery::pattern('#assets/js/settings\.js$#'),
                [],
                Plugin::VERSION,
                true,
            );
        Functions\expect('wp_localize_script')
            ->once()
            ->with('appinio-chat-settings', 'AppinioChatSettings', \Mockery::type('array'));

        (new SettingsPage)->enqueueMedia('settings_page_appinio-chat');

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

    /**
     * OptionsParityTest checks register_setting only, so a field can be registered as an
     * option — saveable by POST, deleted on uninstall — while never appearing on the
     * settings screen. Deleting the add_settings_field call, or passing the wrong
     * section, left the whole suite green.
     */
    public function test_every_option_is_rendered_as_a_field_in_a_declared_section(): void
    {
        $sections = [];
        $fields = [];

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('register_setting')->justReturn(true);
        Functions\when('add_settings_section')->alias(
            function (string $id) use (&$sections): void {
                $sections[] = $id;
            }
        );
        Functions\when('add_settings_field')->alias(
            function (string $id, string $title, $callback, string $page, string $section) use (&$fields): void {
                $fields[$id] = $section;
            }
        );

        (new SettingsPage)->registerSettings();

        foreach (Options::ALL as $option) {
            self::assertArrayHasKey($option, $fields, sprintf('%s has no settings field.', $option));
            self::assertContains(
                $fields[$option],
                $sections,
                sprintf('%s sits in section "%s", which is never declared.', $option, $fields[$option])
            );
        }

        self::assertSame('appinio_chat_appearance', $fields['appinio_chat_launcher']);
        self::assertSame('appinio_chat_appearance', $fields['appinio_chat_launcher_text']);
        self::assertSame('appinio_chat_appearance', $fields['appinio_chat_handoff_button']);
        self::assertSame('appinio_chat_appearance', $fields['appinio_chat_handoff_text']);
    }

    /**
     * The select markup only limits what a browser submits. `sanitize_text_field` stores
     * whatever arrives, so a hand-crafted POST used to persist any string into an
     * enum-valued option. The two new selects validate against their own option list;
     * the three that predate it keep their behaviour on purpose.
     */
    public function test_new_select_fields_reject_values_outside_their_option_list(): void
    {
        $sanitizers = [];

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(null);
        Functions\when('register_setting')->alias(
            function (string $group, string $key, array $args) use (&$sanitizers): void {
                $sanitizers[$key] = $args['sanitize_callback'];
            }
        );

        (new SettingsPage)->registerSettings();

        $launcher = $sanitizers['appinio_chat_launcher'];
        self::assertSame('bar', $launcher('bar'));
        self::assertSame('fab', $launcher('banner'), 'Unknown value must fall back to the default.');
        self::assertSame('fab', $launcher(''));

        $handoff = $sanitizers['appinio_chat_handoff_button'];
        self::assertSame('text', $handoff('text'));
        self::assertSame('icon-text', $handoff('icon-text'));
        self::assertSame('icon', $handoff('chip'));

        // options.php passes null for a field missing from the POST body, and a crafted
        // POST can send an array. Both must fall back quietly rather than emit a notice.
        self::assertSame('icon', $handoff(null));
        self::assertSame('icon', $handoff(['icon']));

        // Untouched by design — flipping these on is a separate change.
        self::assertSame('sanitize_text_field', $sanitizers['appinio_chat_theme']);
        self::assertSame('sanitize_text_field', $sanitizers['appinio_chat_position']);
        self::assertSame('sanitize_text_field', $sanitizers['appinio_chat_auto_open']);
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
        Functions\when('get_admin_page_title')->justReturn('Appinio Chat');
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
        self::assertStringContainsString('Appinio Chat', $output);
        self::assertStringContainsString('<button>Save</button>', $output);
        self::assertStringContainsString('</form>', $output);
    }
}
