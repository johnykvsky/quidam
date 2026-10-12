<?php

declare(strict_types = 1);

namespace Quidam\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Calculator\LuhnCalculatorInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\InvalidLuhnCalculator;
use Quidam\Test\Fixtures\PlPerson;
use Quidam\Test\Fixtures\PlProviderPack;

/**
 * Language providers and replaced definitions - extensions depend on each other by interface, so a replacement is
 * used everywhere
 */
final class ExtendingGeneratorsTest extends TestCase
{
    private const string PL_NAME = '/^(Jan|Piotr|Krzysztof|Andrzej|Tomasz|Anna|Maria|Katarzyna|Małgorzata|Agnieszka) (Nowak|Kowalski|Wiśniewski|Wójcik|Kowalczyk)$/u';

    private const string PL_PART = '/(Nowak|Kowalski|Wiśniewski|Wójcik|Kowalczyk|Jan|Piotr|Krzysztof|Andrzej|Tomasz|Anna|Maria|Katarzyna|Małgorzata|Agnieszka)/iu';

    public function testProviderReplacesExtension(): void
    {
        $person = Quidam::create()->withProvider(new PlProviderPack())->ext(PersonExtensionInterface::class);

        for ($i = 0; $i < 50; ++$i) {
            self::assertMatchesRegularExpression(self::PL_NAME, $person->name());
            self::assertMatchesRegularExpression('/^(Pan|Pani)$/', $person->title());
            self::assertSame('Pan', $person->title(Gender::Male));
        }
    }

    public function testProviderExtensionIsUsedByDependentExtensions(): void
    {
        $generator = Quidam::create()->withProvider(new PlProviderPack());

        for ($i = 0; $i < 50; ++$i) {
            self::assertMatchesRegularExpression('/^(Nowak|Kowalski|Wiśniewski|Wójcik|Kowalczyk)\b/u', $generator->ext(CompanyExtensionInterface::class)->company());
            self::assertMatchesRegularExpression(self::PL_PART, $generator->ext(AddressExtensionInterface::class)->streetName());
            self::assertMatchesRegularExpression(self::PL_NAME, $generator->ext(ProfileExtensionInterface::class)->person()['name']);
        }
    }

    public function testOriginalGeneratorIsNotAffected(): void
    {
        $generator = Quidam::create();
        $polish = $generator->withProvider(new PlProviderPack());

        self::assertInstanceOf(PlPerson::class, $polish->ext(PersonExtensionInterface::class));
        self::assertNotInstanceOf(PlPerson::class, $generator->ext(PersonExtensionInterface::class));
    }

    public function testProviderOverridesData(): void
    {
        $generator = Quidam::create()->withProvider(new PlProviderPack());
        $barcode = $generator->ext(BarcodeExtensionInterface::class);

        for ($i = 0; $i < 50; ++$i) {
            self::assertStringStartsWith('590', $barcode->ean13());
            self::assertMatchesRegularExpression('/^97[89]83\d{8}$/', $barcode->isbn13());
            self::assertStringStartsWith('83', $barcode->isbn10());
        }

        $date = new DateTimeImmutable('2026-10-09 14:05');
        self::assertSame('9 października 2026', $generator->ext(DateTimeExtensionInterface::class)->longDate($date));
    }

    public function testLocaleKeepsCountryConventionsConsistent(): void
    {
        $generator = Quidam::create()->withProvider(new PlProviderPack());
        $dateTime = $generator->ext(DateTimeExtensionInterface::class);
        $payment = $generator->ext(PaymentExtensionInterface::class);
        $phone = $generator->ext(PhoneNumberExtensionInterface::class);
        $date = new DateTimeImmutable('2026-10-09 14:05');

        self::assertSame('09.10.2026 14:05', $dateTime->localDateTime($date));
        self::assertSame("1\u{00A0}234,56\u{00A0}zł", $payment->formatPrice(1234.56));
        self::assertSame(['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł'], $payment->localCurrency());
        self::assertSame('Europe/Warsaw', $dateTime->timezone());
        self::assertStringStartsWith('Europe/', $dateTime->timezone('DE'), 'explicit country still wins');

        for ($i = 0; $i < 50; ++$i) {
            self::assertStringStartsWith('PL', $payment->iban(), 'IBAN of the locale country');
            self::assertMatchesRegularExpression('/^\+48\d{9}$/', $phone->e164PhoneNumber());
            self::assertMatchesRegularExpression('/^\+48 [1-9]\d{1,2} \d{2,3} \d{2,3}( \d{2})?$/', $phone->internationalPhoneNumber());
            self::assertMatchesRegularExpression('/^[5-8]\d{2} \d{3} \d{3}$/', $phone->mobilePhoneNumber());
        }
    }

    public function testE164OfLocaleCountryUsesLocaleFormats(): void
    {
        $pl = Quidam::create(seed: 7)->withProvider(new PlProviderPack());

        for ($i = 0; $i < 20; ++$i) {
            $key = 'phone.' . $i;
            self::assertSame($pl->fork($key)->phoneNumber()->e164PhoneNumber(), $pl->fork($key)->phoneNumber()->e164PhoneNumber('PL'));
            self::assertMatchesRegularExpression('/^\+49[1-9]\d{10}$/', $pl->fork($key)->phoneNumber()->e164PhoneNumber('DE'), 'other country than locale');
        }
    }

    public function testInternationalNumberDropsTrunkPrefix(): void
    {
        $generator = Quidam::create()->withDefinition(LocaleInterface::class, new Locale(countryCode: 'GB', callingCode: '44'));
        $phone = $generator->ext(PhoneNumberExtensionInterface::class);

        for ($i = 0; $i < 50; ++$i) {
            // default national formats start with trunk prefix "0", which isn't dialled from abroad
            self::assertMatchesRegularExpression('/^\+44 [1-9]/', $phone->internationalPhoneNumber());
            self::assertMatchesRegularExpression('/^\+44[1-9]\d{8,10}$/', $phone->e164PhoneNumber());
        }
    }

    public function testNonAsciiNamesGiveAsciiEmailsAndDomains(): void
    {
        $generator = Quidam::create()->withProvider(new PlProviderPack());
        $internet = $generator->ext(InternetExtensionInterface::class);

        $this->assertNameParts($internet->email('Łukasz', 'Wójcik'));
        $this->assertNameParts($internet->userName('Łukasz', "O'Wójcik-Nowak"));
        for ($i = 0; $i < 50; ++$i) {
            self::assertMatchesRegularExpression('/^[a-z0-9._]+@example\.(com|org|net)$/', $internet->email());
            self::assertMatchesRegularExpression('/^[a-z0-9.-]+\.[a-z]+$/', $internet->domainName());
        }
    }

    public function testCalculatorReplacedInContainer(): void
    {
        $generator = Quidam::create()->withDefinition(LuhnCalculatorInterface::class, InvalidLuhnCalculator::class);
        $luhn = Quidam::create()->ext(LuhnCalculatorInterface::class);

        for ($i = 0; $i < 50; ++$i) {
            self::assertFalse($luhn->isValid($generator->ext(PaymentExtensionInterface::class)->creditCardNumber('Visa')));
            self::assertFalse($luhn->isValid($generator->ext(PhoneNumberExtensionInterface::class)->imei()));
            self::assertTrue($luhn->isValid(Quidam::create()->ext(PaymentExtensionInterface::class)->creditCardNumber()));
        }
    }

    /**
     * User name built from transliterated "Łukasz" and "Wójcik..." (formats can use only one of them, or an initial)
     */
    private function assertNameParts(string $value): void
    {
        self::assertMatchesRegularExpression('/^[a-z0-9._@]+(\.[a-z]+)?$/', $value);
        self::assertMatchesRegularExpression('/lukasz|wojcik|^l[a-z]/', $value);
    }
}
