<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Core\PhoneNumber;
use Quidam\Definitions\Calculator\LuhnCalculatorInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;

final class PhoneNumberTest extends GeneratorTestCase
{
    public function testPhoneNumbers(): void
    {
        $phone = $this->dummy->ext(PhoneNumberExtensionInterface::class);

        $this->assertAlwaysMatches('/^0[1-9][\d ]{9,12}$/', $phone->phoneNumber(...));
        $this->assertAlwaysMatches('/^0[1-9][\d ]{9,11}$/', $phone->mobilePhoneNumber(...));
        $this->assertAlwaysMatches('/^\+[1-9]\d{5,14}$/', $phone->e164PhoneNumber(...));
        // random real country without locale calling code, groups of 3 (4 at the end)
        $this->assertAlwaysMatches('/^\+[1-9]\d{0,2}( \d{2,4})+$/', $phone->internationalPhoneNumber(...));
    }

    public function testE164OfGivenCountry(): void
    {
        $phone = $this->dummy->phoneNumber();

        $this->assertAlwaysMatches('/^\+48[1-9]\d{8}$/', static fn (): string => $phone->e164PhoneNumber('PL'));
        $this->assertAlwaysMatches('/^\+1[1-9]\d{9}$/', static fn (): string => $phone->e164PhoneNumber('CA'));
        $this->assertAlwaysMatches('/^\+358[1-9]\d{6,10}$/', static fn (): string => $phone->e164PhoneNumber('AX'));
        $this->assertAlwaysMatches('/^\+86[1-9]\d{10}$/', static fn (): string => $phone->e164PhoneNumber('CN'));
        $this->assertAlwaysMatches('/^\+22901\d{8}$/', static fn (): string => $phone->e164PhoneNumber('BJ')); // leading zeros are literal
    }

    public function testE164OfEveryCountryWithNumbers(): void
    {
        $phone = $this->dummy->phoneNumber();
        $address = new \ReflectionClassConstant(\Quidam\Core\Address::class, 'COUNTRIES')->getValue();
        self::assertIsArray($address);
        $tested = 0;

        foreach ($address as $country) {
            self::assertIsArray($country);
            $countryCode = $country[0];
            self::assertIsString($countryCode);
            if (in_array($countryCode, ['AQ', 'BV', 'GS', 'HM', 'PN', 'TF', 'UM'], true)) {
                continue;
            }

            self::assertMatchesRegularExpression('/^\+[1-9]\d{5,14}$/', $phone->e164PhoneNumber($countryCode), $countryCode);
            ++$tested;
        }

        self::assertSame(242, $tested);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCountries(): iterable
    {
        yield 'unknown' => ['XX'];
        yield 'lowercase' => ['pl'];
        yield 'no own numbers' => ['AQ'];
        yield 'alpha-3' => ['POL'];
    }

    #[DataProvider('invalidCountries')]
    public function testE164OfInvalidCountryThrows(string $countryCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->phoneNumber()->e164PhoneNumber($countryCode);
    }

    public function testInternationalNumberGrouping(): void
    {
        $phone = new class (
            $this->dummy->random(),
            $this->dummy->strings(),
            $this->dummy->ext(LuhnCalculatorInterface::class),
            $this->dummy->ext(LocaleInterface::class),
        ) extends PhoneNumber {
            /** @var list<string> */
            protected const array E164_FORMATS = ['+690 7###', '+690 7####', '+690 7#####', '+690 7######'];
        };

        // groups of 3, a single digit left at the end joins the last group - also when it's the first one
        $this->assertAlwaysMatches('/^\+690 (7\d{3}|7\d{2} \d{2}|7\d{2} \d{3}|7\d{2} \d{4})$/', $phone->internationalPhoneNumber(...));
    }

    public function testImei(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $imei = $this->dummy->ext(PhoneNumberExtensionInterface::class)->imei();
            self::assertMatchesRegularExpression('/^(35|86|01|99)\d{13}$/', $imei);

            $sum = 0;
            foreach (array_reverse(str_split($imei)) as $position => $digit) {
                $value = (int) $digit * ($position % 2 === 1 ? 2 : 1);
                $sum += $value > 9 ? $value - 9 : $value;
            }

            self::assertSame(0, $sum % 10, 'Luhn ' . $imei);
        }
    }
}
