<?php

declare(strict_types=1);

namespace Appinio\Chat\Tests;

use Appinio\Chat\Frontend\ChatWidget;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The two halves of the translation integration are declared in different languages and
 * kept in step by hand: ChatWidget::TRANSLATABLE_OPTIONS drives Polylang (register +
 * pll__), and wpml-config.xml drives WPML, which filters get_option() itself and needs no
 * PHP at all. Nothing connected them until this test — an option added to one and not the
 * other translates on one plugin and silently ships English on the other.
 */
final class TranslatableOptionsTest extends TestCase
{
    public function test_wpml_config_declares_exactly_the_polylang_translatable_options(): void
    {
        $polylang = $this->translatableOptions();
        $wpml = $this->wpmlAdminTextKeys();

        sort($polylang);
        sort($wpml);

        self::assertSame(
            $polylang,
            $wpml,
            'TRANSLATABLE_OPTIONS and wpml-config.xml <admin-texts> must list the same options.'
        );
    }

    public function test_every_translatable_option_is_actually_rendered_as_an_attribute(): void
    {
        $attributeMap = $this->constant('ATTRIBUTE_MAP');

        foreach ($this->translatableOptions() as $option) {
            self::assertArrayHasKey(
                $option,
                $attributeMap,
                sprintf('%s is translated but never reaches the widget.', $option)
            );
        }
    }

    /** @return list<string> */
    private function translatableOptions(): array
    {
        /** @var list<string> $options */
        $options = $this->constant('TRANSLATABLE_OPTIONS');

        return $options;
    }

    /** @return list<string> */
    private function wpmlAdminTextKeys(): array
    {
        $path = \dirname(__DIR__).'/wpml-config.xml';
        self::assertFileExists($path);

        $xml = simplexml_load_file($path);
        self::assertNotFalse($xml, 'wpml-config.xml is not valid XML.');

        $keys = [];
        foreach ($xml->{'admin-texts'}->key as $key) {
            $keys[] = (string) $key['name'];
        }

        return $keys;
    }

    private function constant(string $name): mixed
    {
        return (new ReflectionClass(ChatWidget::class))->getConstant($name);
    }
}
