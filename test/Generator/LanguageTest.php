<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\LanguageExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class LanguageTest extends GeneratorTestCase
{
    public function testLanguages(): void
    {
        $language = $this->dummy->ext(LanguageExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z]{2}$/', $language->languageCode(...));
        $this->assertAlwaysMatches('/^\S.+$/u', $language->language(...));

        $codes = [];
        for ($i = 0; $i < 1000; ++$i) {
            $data = $language->languageData();
            self::assertSame($data, $codes[$data['code']] ?? $data, 'same code always has same name');
            $codes[$data['code']] = $data;
        }

        self::assertArrayNotHasKey('vo', $codes, 'no constructed languages');
        self::assertArrayNotHasKey('la', $codes, 'no historical languages');
        self::assertGreaterThan(150, count($codes));
    }

    public function testLocale(): void
    {
        $language = $this->dummy->ext(LanguageExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z]{2}_[A-Z]{2}$/', static fn (): string => $language->locale());
        $this->assertAlwaysMatches('/^[a-z]{2}-[A-Z]{2}$/', static fn (): string => $language->locale('-'));
    }

    public function testInvalidLocaleSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(LanguageExtensionInterface::class)->locale('.');
    }
}
