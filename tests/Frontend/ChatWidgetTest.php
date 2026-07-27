<?php

declare(strict_types=1);

namespace Appinio\Chat\Tests\Frontend;

use Appinio\Chat\Frontend\ChatWidget;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class ChatWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Brain Monkey leaves a stubbed function *declared* after tearDown(), so once any
        // test in this file stubs pll__ or pll_current_language, function_exists() stays
        // true for the rest of the process and ChatWidget calls them unmocked. The suite
        // then passes only because the Polylang tests happen to be declared last:
        // `phpunit --order-by=reverse` errored eight tests before these defaults existed.
        // Individual tests still override them.
        Functions\when('pll__')->returnArg();
        Functions\when('pll_current_language')->justReturn('');
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
            'appinio_chat_site_id' => 'ch_abc123',
            default => $default,
        });

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'appinio-chat-widget',
                'https://cdn.app-in.io/v1/chat.js',
                [],
                null,
                ['strategy' => 'defer', 'in_footer' => true]
            );

        $widget = new ChatWidget;
        $widget->enqueueAssets();

        self::assertTrue(true);
    }

    public function test_cdn_url_defaults_to_production_cdn(): void
    {
        self::assertSame('https://cdn.app-in.io/v1/chat.js', ChatWidget::cdnUrl());
    }

    public function test_cdn_url_is_overridable_via_filter(): void
    {
        Functions\when('apply_filters')->alias(
            fn (string $hook, $value) => $hook === 'appinio_chat_cdn_url'
                ? 'http://localhost:5174/src/chat/loader.ts'
                : $value
        );

        self::assertSame('http://localhost:5174/src/chat/loader.ts', ChatWidget::cdnUrl());
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
            'appinio_chat_site_id' => 'ch_abc123',
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
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_title' => 'Help Bot',
            'appinio_chat_subtitle' => 'We are here to help',
            'appinio_chat_logo_url' => 'https://example.com/logo.png',
            'appinio_chat_theme' => 'dark',
            'appinio_chat_position' => 'bottom-left',
            'appinio_chat_launcher' => 'bar',
            'appinio_chat_launcher_text' => 'Kirjuta meile',
            'appinio_chat_handoff_button' => 'icon-text',
            'appinio_chat_handoff_text' => 'Räägi agendiga',
            'appinio_chat_lang' => 'de',
            'appinio_chat_accent_color' => '#FF5500',
            'appinio_chat_price_prefix' => 'from',
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
        self::assertStringContainsString('launcher="bar"', $output);
        self::assertStringContainsString('launcher-text="Kirjuta meile"', $output);
        self::assertStringContainsString('handoff-button="icon-text"', $output);
        self::assertStringContainsString('handoff-text="Räägi agendiga"', $output);
        self::assertStringContainsString('accent-color="#FF5500"', $output);
        self::assertStringContainsString('price-prefix="from"', $output);
    }

    public function test_render_element_omits_empty_label_attributes(): void
    {
        // The omission is the feature, not an oversight: with no attribute the widget
        // uses its own string, which is translated into 12 languages and follows the
        // `lang` we send. Emitting an empty — or an English default — would replace 12
        // languages with one. This is the whole answer for sites with no Polylang/WPML.
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_launcher' => 'bar',
            'appinio_chat_handoff_button' => 'icon-text',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('has_filter')->justReturn(false);
        // No appinio_chat_lang here, so resolveLang() walks on to the site locale.
        Functions\when('get_locale')->justReturn('et');

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('launcher="bar"', $output);
        self::assertStringContainsString('handoff-button="icon-text"', $output);
        self::assertStringNotContainsString('launcher-text', $output);
        self::assertStringNotContainsString('handoff-text', $output);
        // The language still reaches the widget — that is what picks the built-in wording.
        self::assertStringContainsString('lang="et"', $output);
    }

    public function test_render_element_emits_auto_open_once_with_delay(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_auto_open' => 'once',
            'appinio_chat_auto_open_delay' => '8',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('auto-open="once"', $output);
        self::assertStringContainsString('auto-open-delay="8"', $output);
    }

    public function test_render_element_emits_auto_open_always(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_auto_open' => 'always',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('auto-open="always"', $output);
        // No delay configured → attribute omitted, widget falls back to its default.
        self::assertStringNotContainsString('auto-open-delay', $output);
    }

    public function test_render_element_omits_auto_open_when_never(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_auto_open' => 'never',
            'appinio_chat_auto_open_delay' => '5',
            default => $default,
        });

        Functions\when('esc_attr')->returnArg();
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('has_filter')->justReturn(false);

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        // "never" is the default — neither attribute should be emitted.
        self::assertStringNotContainsString('auto-open', $output);
    }

    public function test_render_element_with_css_custom_properties(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_color_primary' => '#37B7FF',
            'appinio_chat_color_surface' => '#FFFFFF',
            'appinio_chat_color_text' => '#18181B',
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
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_font' => 'Roboto, sans-serif',
            'appinio_chat_heading_font' => 'Montserrat, sans-serif',
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
        $result = $widget->addModuleType($tag, 'appinio-chat-widget');

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
            'appinio_chat_lang' => 'fr',
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
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_title' => 'Help Bot',
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

    public function test_polylang_translates_the_launcher_and_handoff_labels(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_site_id' => 'ch_abc123',
            'appinio_chat_launcher_text' => 'Chat with us',
            'appinio_chat_handoff_text' => 'Talk to a person',
            default => $default,
        });

        Functions\when('pll__')->alias(fn (string $str) => match ($str) {
            'Chat with us' => 'Schreiben Sie uns',
            'Talk to a person' => 'Mit einer Person sprechen',
            default => $str,
        });

        Functions\when('pll_current_language')->justReturn('de');
        Functions\when('esc_attr')->returnArg();

        $widget = new ChatWidget;

        ob_start();
        $widget->renderElement();
        $output = ob_get_clean();

        self::assertStringContainsString('launcher-text="Schreiben Sie uns"', $output);
        self::assertStringContainsString('handoff-text="Mit einer Person sprechen"', $output);
    }

    public function test_register_polylang_strings_registers_every_filled_translatable_option(): void
    {
        Functions\when('get_option')->alias(fn ($key, $default = '') => match ($key) {
            'appinio_chat_title' => 'Help Bot',
            'appinio_chat_launcher_text' => 'Chat with us',
            'appinio_chat_handoff_text' => 'Talk to a person',
            default => $default,
        });

        $registered = [];
        Functions\when('pll_register_string')->alias(
            function (string $name, string $value) use (&$registered): void {
                $registered[$name] = $value;
            }
        );

        ChatWidget::registerPolylangStrings();

        self::assertSame([
            'appinio_chat_title' => 'Help Bot',
            'appinio_chat_launcher_text' => 'Chat with us',
            'appinio_chat_handoff_text' => 'Talk to a person',
        ], $registered);
        // Empty options are skipped — an empty Polylang string is nothing to translate.
        self::assertArrayNotHasKey('appinio_chat_subtitle', $registered);
    }
}
