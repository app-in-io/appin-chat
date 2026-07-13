<?php

declare(strict_types=1);

namespace AppInIo\Chat\Tests;

use AppInIo\Chat\Migration;
use AppInIo\Chat\Options;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    /** @var array<string, mixed> Fake wp_options table. */
    private array $options = [];

    /** @var list<string> */
    private array $deleted = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->options = [];
        $this->deleted = [];

        Functions\when('get_option')->alias(
            fn (string $key, $default = false) => \array_key_exists($key, $this->options)
                ? $this->options[$key]
                : $default
        );

        Functions\when('update_option')->alias(function (string $key, $value): bool {
            $this->options[$key] = $value;

            return true;
        });

        Functions\when('delete_option')->alias(function (string $key): bool {
            unset($this->options[$key]);
            $this->deleted[] = $key;

            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_it_renames_legacy_options_to_the_appinio_prefix(): void
    {
        $this->options['appin_chat_site_id'] = 'ch_abc123';
        $this->options['appin_chat_theme'] = 'dark';

        Migration::maybeRun();

        self::assertSame('ch_abc123', $this->options['appinio_chat_site_id']);
        self::assertSame('dark', $this->options['appinio_chat_theme']);
        self::assertArrayNotHasKey('appin_chat_site_id', $this->options);
        self::assertArrayNotHasKey('appin_chat_theme', $this->options);
    }

    public function test_it_migrates_the_auto_open_options_uninstall_used_to_leak(): void
    {
        $this->options['appin_chat_auto_open'] = 'once';
        $this->options['appin_chat_auto_open_delay'] = 3;

        Migration::maybeRun();

        self::assertSame('once', $this->options['appinio_chat_auto_open']);
        self::assertSame(3, $this->options['appinio_chat_auto_open_delay']);
    }

    public function test_it_preserves_an_empty_string_value(): void
    {
        // A legitimately-empty setting must survive as '' — not be skipped as "absent".
        $this->options['appin_chat_subtitle'] = '';

        Migration::maybeRun();

        self::assertArrayHasKey('appinio_chat_subtitle', $this->options);
        self::assertSame('', $this->options['appinio_chat_subtitle']);
    }

    public function test_it_never_overwrites_an_existing_new_option(): void
    {
        $this->options['appin_chat_site_id'] = 'old';
        $this->options['appinio_chat_site_id'] = 'new';

        Migration::maybeRun();

        self::assertSame('new', $this->options['appinio_chat_site_id']);
        self::assertArrayNotHasKey('appin_chat_site_id', $this->options);
    }

    public function test_it_records_the_version_and_is_a_no_op_on_the_next_run(): void
    {
        $this->options['appin_chat_site_id'] = 'ch_abc123';

        Migration::maybeRun();

        self::assertSame(Migration::SCHEMA, $this->options[Options::VERSION]);

        $this->deleted = [];
        Migration::maybeRun();

        self::assertSame([], $this->deleted, 'a second run must touch nothing');
    }

    public function test_it_is_harmless_on_a_fresh_install(): void
    {
        Migration::maybeRun();

        self::assertSame([Options::VERSION => Migration::SCHEMA], $this->options);
    }
}
