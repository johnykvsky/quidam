<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack;

use PHPUnit\Framework\TestCase;
use Quidam\DeadLanguagePack\AncientLanguages;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Quidam;

final class AncientLanguagesTest extends TestCase
{
    /** Packs ancient() doesn't pick - Klingon was never alive */
    private const array NOT_ANCIENT = ['tlh_QO'];

    public function testEveryHistoricalPackIsListed(): void
    {
        $directories = array_map(basename(...), glob(dirname(__DIR__, 2) . '/src/DeadLanguagePack/*', GLOB_ONLYDIR) ?: []);
        $codes = [...array_keys(AncientLanguages::PACKS), ...self::NOT_ANCIENT];
        sort($directories);
        sort($codes);

        self::assertSame($directories, $codes, 'a new pack goes to AncientLanguages::PACKS, or to NOT_ANCIENT here');

        foreach (AncientLanguages::PACKS as $code => $class) {
            self::assertStringContainsString('\\' . $code . '\\', $class);
        }
    }

    public function testRandomAncientLanguageFollowsTheSeed(): void
    {
        $expected = array_map(static fn (string $code): string => explode('_', $code)[0], array_keys(AncientLanguages::PACKS));
        $languages = [];

        for ($seed = 1; $seed <= 200; ++$seed) {
            $language = Quidam::create(seed: $seed)->ancient()->ext(LocaleInterface::class)->languageCode();
            $languages[$language] = true;

            self::assertSame($language, Quidam::create(seed: $seed)->ancient()->ext(LocaleInterface::class)->languageCode(), 'the same seed, the same language');
        }

        $picked = array_keys($languages);
        sort($picked);
        sort($expected);
        self::assertSame($expected, $picked, 'every ancient language gets picked, Klingon never');
    }

    public function testGeneratorItselfIsUnchanged(): void
    {
        $generator = Quidam::create(seed: 2026);
        $ancient = $generator->ancient();

        self::assertNotSame($generator, $ancient);
        self::assertSame('en', $generator->ext(LocaleInterface::class)->languageCode());
        self::assertNotSame('en', $ancient->ext(LocaleInterface::class)->languageCode());
    }
}
