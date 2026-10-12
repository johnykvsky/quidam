<?php

declare(strict_types = 1);

namespace Quidam\Test\Locale;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class LocaleTest extends TestCase
{
    public function testDefaultIsInternational(): void
    {
        $locale = Quidam::create()->ext(LocaleInterface::class);

        self::assertNull($locale->countryCode());
        self::assertNull($locale->callingCode());
        self::assertSame('en', $locale->languageCode());
        self::assertSame('0', $locale->trunkPrefix());
        self::assertSame(['EUR', '€'], [$locale->currencyCode(), $locale->currencySymbol()]);
        self::assertSame(['.', ','], [$locale->decimalSeparator(), $locale->thousandsSeparator()]);
        self::assertSame("{amount}\u{00A0}{code}", $locale->pricePattern());
        self::assertSame(['Y-m-d', 'H:i', '{day} {month} {year}'], [$locale->dateFormat(), $locale->timeFormat(), $locale->longDateFormat()]);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function invalid(): iterable
    {
        yield 'lowercase country' => [['countryCode' => 'pl']];
        yield 'alpha-3 country' => [['countryCode' => 'POL']];
        yield 'calling code with plus' => [['callingCode' => '+48']];
        yield 'calling code too long' => [['callingCode' => '1234']];
        yield 'currency' => [['currencyCode' => 'zl']];
    }

    /** @param array<string, string> $arguments */
    #[DataProvider('invalid')]
    public function testInvalidValues(array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Locale(...$arguments);
    }
}
