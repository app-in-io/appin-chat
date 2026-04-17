<?php

declare(strict_types=1);

namespace AppIn\Chat\Tests\Frontend;

use AppIn\Chat\Frontend\ChatWidget;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class ChatWidgetTest extends TestCase
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
        $widget = new ChatWidget;
        $widget->register();

        self::assertNotFalse(has_action('wp_enqueue_scripts', [$widget, 'enqueueAssets']));
        self::assertNotFalse(has_filter('script_loader_tag', [$widget, 'addModuleType']));
        self::assertNotFalse(has_action('wp_footer', [$widget, 'renderElement']));
    }

    public function test_enqueue_assets_when_site_id_set(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            default => $default,
        });

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'appin-chat-widget',
                APPIN_CHAT_CDN_URL,
                [],
                null,
                ['strategy' => 'defer', 'in_footer' => true]
            );

        $widget = new ChatWidget;
        $widget->enqueueAssets();

        self::assertTrue(true);
    }

    public function test_enqueue_assets_skipped_when_no_site_id(): void
    {
        Functions\when('get_option')->justReturn('');

        Functions\expect('wp_enqueue_script')->never();

        $widget = new ChatWidget;
        $widget->enqueueAssets();

        self::assertTrue(true);
    }

    public function test_render_element_with_site_id(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('<app-in-chat', $output);
        self::assertStringContainsString('site-id="ch_abc123"', $output);
        self::assertStringContainsString('lang="en"', $output);
    }

    public function test_render_element_skipped_when_no_site_id(): void
    {
        Functions\when('get_option')->justReturn('');

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertEmpty($output);
    }

    public function test_render_element_with_all_attributes(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            'appin_chat_title' => 'Help Bot',
            'appin_chat_subtitle' => 'We are here to help',
            'appin_chat_logo_url' => 'https://example.com/logo.png',
            'appin_chat_theme' => 'dark',
            'appin_chat_position' => 'bottom-left',
            'appin_chat_lang' => 'de',
            'appin_chat_accent_color' => '#FF5500',
            'appin_chat_price_prefix' => 'from',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('site-id="ch_abc123"', $output);
        self::assertStringContainsString('title="Help Bot"', $output);
        self::assertStringContainsString('subtitle="We are here to help"', $output);
        self::assertStringContainsString('logo-url="https://example.com/logo.png"', $output);
        self::assertStringContainsString('theme="dark"', $output);
        self::assertStringContainsString('position="bottom-left"', $output);
        self::assertStringContainsString('accent-color="#FF5500"', $output);
        self::assertStringContainsString('price-prefix="from"', $output);
    }

    public function test_render_element_with_css_custom_properties(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            'appin_chat_color_primary' => '#37B7FF',
            'appin_chat_color_surface' => '#FFFFFF',
            'appin_chat_color_text' => '#18181B',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('style="', $output);
        self::assertStringContainsString('--app-in-primary: #37B7FF', $output);
        self::assertStringContainsString('--app-in-surface: #FFFFFF', $output);
        self::assertStringContainsString('--app-in-text: #18181B', $output);
    }

    public function test_render_element_with_custom_fonts(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            'appin_chat_font' => 'Roboto, sans-serif',
            'appin_chat_heading_font' => 'Montserrat, sans-serif',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('--app-in-font: Roboto, sans-serif', $output);
        self::assertStringContainsString('--app-in-heading-font: Montserrat, sans-serif', $output);
    }

    public function test_add_module_type_to_script_tag(): void
    {
        $widget = new ChatWidget;

        $tag = '<script src="https://cdn.app-in.io/v1/chat.js"></script>';
        $result = $widget->addModuleType($tag, 'appin-chat-widget');

        self::assertStringContainsString('type="module"', $result);
    }

    public function test_add_module_type_ignores_other_scripts(): void
    {
        $widget = new ChatWidget;

        $tag = '<script src="https://example.com/other.js"></script>';
        $result = $widget->addModuleType($tag, 'other-script');

        self::assertStringNotContainsString('type="module"', $result);
    }

    public function test_resolve_lang_from_polylang(): void
    {
        Functions\when('pll_current_language')->justReturn('de');
        Functions\when('get_option')->justReturn('');
        Functions\when('esc_attr')->returnArg();

        $widget = new ChatWidget;

        self::assertSame('de', $widget->resolveLang());
    }

    public function test_resolve_lang_from_manual_setting(): void
    {
        // pll_current_language may exist from prior test — return empty to skip Polylang path
        Functions\when('pll_current_language')->justReturn('');
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_lang' => 'fr',
            default => $default,
        });

        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        self::assertSame('fr', $widget->resolveLang());
    }

    public function test_resolve_lang_from_wp_locale(): void
    {
        Functions\when('pll_current_language')->justReturn('');
        Functions\when('get_option')->justReturn('');
        Functions\when('get_locale')->justReturn('de_DE');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        self::assertSame('de', $widget->resolveLang());
    }

    public function test_polylang_translates_title(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appin_chat_site_id' => 'ch_abc123',
            'appin_chat_title' => 'Help Bot',
            default => $default,
        });

        Functions\when('pll__')->alias(fn (string $str) => match ($str) {
            'Help Bot' => 'Hilfe-Bot',
            default => $str,
        });

        Functions\when('pll_current_language')->justReturn('de');
        Functions\when('esc_attr')->returnArg();

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('title="Hilfe-Bot"', $output);
    }
}
