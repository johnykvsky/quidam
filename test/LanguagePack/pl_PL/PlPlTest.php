<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\pl_PL;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Addon\Template;
use Quidam\Core\Randomizer\SecureRandomizer;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Exception\ContainerException;
use Quidam\Exception\InvalidArgumentException;
use Quidam\LanguagePack\pl_PL\Address;
use Quidam\LanguagePack\pl_PL\Commerce;
use Quidam\LanguagePack\pl_PL\DateTime;
use Quidam\LanguagePack\pl_PL\Internet;
use Quidam\LanguagePack\pl_PL\NationalId;
use Quidam\LanguagePack\pl_PL\Payment;
use Quidam\LanguagePack\pl_PL\Person;
use Quidam\LanguagePack\pl_PL\PhoneNumber;
use Quidam\LanguagePack\pl_PL\PlPlGenerator;
use Quidam\LanguagePack\pl_PL\PlPlProviderPack;
use Quidam\LanguagePack\pl_PL\Profile;
use Quidam\LanguagePack\pl_PL\Text;
use Quidam\Quidam;
use ReflectionClassConstant;
use ReflectionMethod;

final class PlPlTest extends TestCase
{
    private const int REPEAT = 200;

    private PlPlGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = PlPlGenerator::create();
    }

    public function testPluggingIn(): void
    {
        $generator = $this->generator;

        self::assertInstanceOf(Person::class, $generator->person());
        self::assertInstanceOf(Address::class, $generator->address());
        self::assertInstanceOf(DateTime::class, $generator->dateTime());
        self::assertInstanceOf(Internet::class, $generator->internet());
        self::assertInstanceOf(Commerce::class, $generator->commerce());
        self::assertInstanceOf(Profile::class, $generator->profile());
        self::assertInstanceOf(Text::class, $generator->text());
        self::assertSame($generator->address(), $generator->ext(AddressExtensionInterface::class), 'one instance, under the interface');

        // the pack works on any generator, also without PlPlGenerator
        $plain = Quidam::create(seed: 1)->withProvider(new PlPlProviderPack());
        self::assertInstanceOf(Person::class, $plain->person());
        self::assertInstanceOf(Text::class, $plain->ext(TextInterface::class));

        self::assertInstanceOf(Person::class, PlPlGenerator::createSecure()->person(), 'secure() keeps the pack');
        self::assertInstanceOf(SecureRandomizer::class, PlPlGenerator::createSecure()->random());
        self::assertInstanceOf(PlPlGenerator::class, $generator->fork('users.1')->withDefinition('foo', new \stdClass()), 'class kept');
        self::assertSame(PlPlGenerator::create(seed: 7)->person()->name(), PlPlGenerator::create(seed: 7)->person()->name());
        self::assertSame($plain->person()->name(), PlPlGenerator::create(seed: 1)->person()->name(), 'the same data both ways');
    }

    public function testOwnTemplateAliasesStayWithThePack(): void
    {
        // aliases are separate container entries - the pack adds nationalId without dropping yours
        $template = Quidam::create()
            ->withDefinition(Template::alias('bank'), PaymentExtensionInterface::class)
            ->withProvider(new PlPlProviderPack())
            ->ext(TemplateInterface::class);

        [$pesel, $bank] = explode(' ', $template->parse('{{ nationalId.pesel }} {{ bank.bank }}'), 2);
        self::assertTrue(Validators::pesel($pesel), $pesel);
        $banks = new ReflectionClassConstant(Payment::class, 'BANKS')->getValue();
        self::assertIsArray($banks);
        self::assertContains($bank, $banks);
    }

    public function testAccessorNeedsPackClass(): void
    {
        $generator = $this->generator->withDefinition(PhoneNumberExtensionInterface::class, \Quidam\Core\PhoneNumber::class);

        self::assertNotInstanceOf(PhoneNumber::class, $generator->ext(PhoneNumberExtensionInterface::class), 'ext() still works');
        $this->expectException(ContainerException::class);
        $generator->phoneNumber();
    }

    public function testGenderedSurnames(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertDoesNotMatchRegularExpression('/(s|c|dz)ka$/u', $person->lastName(Gender::Male));
            self::assertDoesNotMatchRegularExpression('/((s|c|dz)ki|y)$/u', $person->lastName(Gender::Female));

            $profile = $this->generator->profile()->person();
            $wrongForm = $profile['gender'] === Gender::Female ? '/((s|c|dz)ki|y)$/u' : '/(s|c|dz)ka$/u';
            self::assertDoesNotMatchRegularExpression($wrongForm, $profile['lastName'], 'surname in the form of the gender');
        }

        $feminine = new ReflectionMethod($person, 'feminine');
        self::assertSame('Kowalska', $feminine->invoke($person, 'Kowalski'));
        self::assertSame('Zawadzka', $feminine->invoke($person, 'Zawadzki'));
        self::assertSame('Konieczna', $feminine->invoke($person, 'Konieczny'));
        self::assertSame('Nowak', $feminine->invoke($person, 'Nowak'));
        self::assertContains($person->title(), ['mgr', 'mgr inż.', 'inż.', 'dr', 'dr inż.', 'dr hab.', 'prof.']);
    }

    public function testAddress(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^(ul|al|pl|os)\. .+ [1-9]\d*[AB]?(\/[1-9]\d*)?, \d{2}-\d{3} \p{Lu}.+$/u', $address->address());
            self::assertMatchesRegularExpression('/^PL-(0[2468]|[12][02468]|3[02])$/', $address->stateCode());
            self::assertDoesNotMatchRegularExpression('/^\w+\. (Al|Pl|Os)\. /u', $address->streetName(), 'one street type');
            self::assertMatchesRegularExpression('/^(m|lok)\. [1-9]\d*$/', $address->secondaryAddress());
            self::assertMatchesRegularExpression('/^powiat \p{Ll}+|^\p{Lu}/u', $address->county());
        }

        $countries = new ReflectionClassConstant(Address::class, 'COUNTRIES')->getValue();
        self::assertIsArray($countries);
        self::assertContains(['DE', 'DEU', 'Niemcy'], $countries);
        self::assertContains(['GB', 'GBR', 'Wielka Brytania'], $countries);
    }

    public function testPhoneNumbers(): void
    {
        $phone = $this->generator->phoneNumber();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^(45|5[0137]|6[069]|7[2389]|88)\d \d{3} \d{3}$/', $phone->mobilePhoneNumber());
            self::assertMatchesRegularExpression('/^\d{2} [1-9]\d{2} \d{2} \d{2}$/', $phone->phoneNumber());
            self::assertMatchesRegularExpression('/^\+48 \d{2,3} \d{2,3} \d{2,3}( \d{2})?$/', $phone->internationalPhoneNumber());
            self::assertMatchesRegularExpression('/^\+48[1-9]\d{8}$/', $phone->e164PhoneNumber());
            self::assertMatchesRegularExpression('/^800[ -]\d{3}[ -]\d{3}$/', $phone->tollFreePhoneNumber());
            self::assertMatchesRegularExpression('/^\+49[1-9]\d{10}$/', $phone->e164PhoneNumber('DE'), 'other country');
        }
    }

    public function testE164OfLocaleCountryUsesPackFormats(): void
    {
        $generator = PlPlGenerator::create(seed: 3);

        for ($i = 0; $i < 20; ++$i) {
            $key = 'phone.' . $i;
            self::assertSame($generator->fork($key)->phoneNumber()->e164PhoneNumber(), $generator->fork($key)->phoneNumber()->e164PhoneNumber('PL'));
        }
    }

    public function testCompanyAndInternet(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $company = $this->generator->company()->company();
            self::assertStringNotContainsString('{', $company);
            self::assertDoesNotMatchRegularExpression('/(s|c|dz)ka\b/u', $company, 'base form of surnames');
            self::assertMatchesRegularExpression('/^[a-z0-9._]+@example\.(com|org|net)$/', $this->generator->internet()->email(), 'ASCII only');
            self::assertMatchesRegularExpression('/^[a-z0-9-]+\.[a-z.]+$/', $this->generator->internet()->domainName());
            self::assertMatchesRegularExpression('/^[a-z0-9._%+-]+@[a-z0-9.-]+$/', $this->generator->internet()->freeEmail());
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]*(lucja|zolc)[a-z0-9._]*$/', $this->generator->internet()->userName('Łucja', 'Żółć'));
    }

    public function testBankAccounts(): void
    {
        $payment = $this->generator->payment();
        $banks = new ReflectionClassConstant(Payment::class, 'BANKS')->getValue();
        self::assertIsArray($banks);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $iban = $payment->iban();
            self::assertMatchesRegularExpression('/^PL\d{26}$/', $iban);
            self::assertTrue(Validators::iban($iban), $iban);
            self::assertTrue(Validators::polishSortCode(substr($iban, 4, 8)), 'sort code of ' . $iban);
            self::assertArrayHasKey((int) substr($iban, 4, 3), $banks, 'real bank code');

            $nrb = $payment->bankAccountNumber();
            self::assertMatchesRegularExpression('/^\d{26}$/', $nrb);
            self::assertTrue(Validators::iban('PL' . $nrb));
        }

        self::assertStringStartsWith('102', $payment->sortCode('102'));
        self::assertTrue(Validators::polishSortCode($payment->sortCode('102')));
        self::assertMatchesRegularExpression('/^DE\d{20}$/', $payment->iban('DE'));
        self::assertMatchesRegularExpression('/^\d{12}$/', $payment->bankAccountNumber(12));
        self::assertContains($payment->bank(), $banks);

        $this->expectException(InvalidArgumentException::class);
        $payment->sortCode('999');
    }

    public function testLicensePlates(): void
    {
        $vehicle = $this->generator->vehicle();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z]{2,3} [A-Z0-9]{4,5}$/', $vehicle->licensePlate());
            self::assertMatchesRegularExpression('/^[WA]/', $vehicle->licensePlate('mazowieckie'));
            self::assertMatchesRegularExpression('/^[A-Z]{2,4} [A-Z0-9]{4,5}$/', $vehicle->licensePlate(special: true));
        }

        $this->expectException(InvalidArgumentException::class);
        $vehicle->licensePlate('bawaria');
    }

    public function testNationalIds(): void
    {
        $id = $this->generator->nationalId();

        self::assertTrue(Validators::pesel('44051401359'), 'validator accepts the published example');
        self::assertTrue(Validators::polishIdCard('ABA300000'), 'validator accepts the published example');

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertTrue(Validators::pesel($pesel = $id->pesel()), $pesel);
            self::assertTrue(Validators::nip($nip = $id->nip()), $nip);
            self::assertMatchesRegularExpression('/^\d{3}-\d{3}-\d{2}-\d{2}$/', $id->nip(formatted: true));
            self::assertTrue(Validators::regon($regon = $id->regon()), $regon);
            self::assertTrue(Validators::regon($regon = $id->regonLocal()), $regon);
            self::assertTrue(Validators::polishIdCard($card = $id->idCardNumber()), $card);
        }
    }

    public function testPeselEncodesBirthDateAndGender(): void
    {
        $id = $this->generator->nationalId();

        foreach (['1899-12-31', '1950-06-15', '2000-02-29', '2024-11-30', '2150-01-01', '2299-12-31'] as $date) {
            foreach ([Gender::Male, Gender::Female] as $gender) {
                $pesel = $id->pesel(new DateTimeImmutable($date), $gender);
                self::assertTrue(Validators::pesel($pesel), $pesel);
                self::assertSame([$date, $gender->value], Validators::peselData($pesel));
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $id->pesel(new DateTimeImmutable('1799-12-31'));
    }

    public function testMoreIdentifiers(): void
    {
        $id = $this->generator->nationalId();

        self::assertTrue(Validators::polishPassport('CC7999486'), 'validator accepts the published example');
        self::assertTrue(Validators::landRegistry('ZG1G/00101235/2'), 'validator accepts the kw-cyfra example');
        self::assertTrue(Validators::landRegistry('CIKW/00123111/7'), 'validator accepts the kw-cyfra example');
        self::assertFalse(Validators::landRegistry('CIKW/00123111/8'));

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertTrue(Validators::polishPassport($passport = $id->passportNumber()), $passport);
            self::assertStringStartsWith('E', $passport);
            self::assertTrue(Validators::landRegistry($kw = $id->landRegistryNumber()), $kw);
            self::assertTrue(Validators::doctorLicence($pwz = $id->doctorLicenceNumber()), $pwz);
            self::assertMatchesRegularExpression('/^0{3}\d{7}$/', $id->krs());
            $vat = $id->euVatNumber();
            self::assertStringStartsWith('PL', $vat);
            self::assertTrue(Validators::nip(substr($vat, 2)), $vat);
        }

        $courts = new ReflectionClassConstant(NationalId::class, 'LAND_REGISTRY_COURTS')->getValue();
        self::assertIsArray($courts);
        self::assertCount(343, $courts);
        self::assertNotContains('GL1X', $courts);
        self::assertContains('WA1M', $courts);
    }

    public function testCitizen(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $citizen = $this->generator->profile()->citizen();

            self::assertSame(
                [$citizen['dateOfBirth']->format('Y-m-d'), $citizen['gender']->value],
                Validators::peselData($citizen['pesel']),
                'PESEL matches birth date and gender',
            );
            self::assertTrue(Validators::pesel($citizen['pesel']));
            self::assertTrue(Validators::polishIdCard($citizen['idCardNumber']));
            self::assertTrue(Validators::polishPassport($citizen['passportNumber']));
            self::assertMatchesRegularExpression('/^\d{3} \d{3} \d{3}$/', $citizen['phone']);
            self::assertSame('pl', $citizen['language'], 'language of the locale');
            self::assertStringContainsString($citizen['address']['city'], $citizen['address']['address']);
            $wrongForm = $citizen['gender'] === Gender::Female ? '/((s|c|dz)ki|y)$/u' : '/(s|c|dz)ka$/u';
            self::assertDoesNotMatchRegularExpression($wrongForm, $citizen['lastName']);
        }

        $citizen = $this->generator->profile()->citizen(Gender::Female, 30, 30);
        self::assertSame(30, $citizen['age']);
        self::assertSame('female', Validators::peselData($citizen['pesel'])[1]);
    }

    public function testText(): void
    {
        $text = $this->generator->text()->realText(50, 200);

        self::assertGreaterThanOrEqual(50, mb_strlen($text));
        self::assertMatchesRegularExpression('/[ąćęłńóśźż]/u', $this->generator->text()->realText(500, 2000), 'Polish text');
    }

    public function testCommerceAdjectivesAgreeWithProduct(): void
    {
        $genders = new ReflectionClassConstant(Commerce::class, 'PRODUCT_GENDER')->getValue();
        self::assertIsArray($genders);
        self::assertSame(array_keys($genders), new ReflectionClassConstant(Commerce::class, 'PRODUCT')->getValue());

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $this->generator->commerce()->productName();
            $words = explode(' ', $name);
            $product = mb_convert_case(end($words), MB_CASE_TITLE);
            $ending = match ($genders[$product] ?? null) {
                Commerce::FEMININE => '/^(\S+a )+\S+$/u',
                Commerce::NEUTER => '/^(\S+e )+\S+$/u',
                default => '/^(\S+[yi] )+\S+$/u',
            };
            self::assertMatchesRegularExpression($ending, (string) preg_replace('/^Ręcznie /u', '', $name), $name);
            self::assertMatchesRegularExpression('/^[A-Z]+\d+$/', $this->generator->commerce()->promotionCode());
        }
    }

    public function testCatchPhraseAndCurrency(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^\p{Lu}\p{Ll}+e \p{Ll}+ .+$/u', $this->generator->company()->catchPhrase());
        }

        self::assertSame(['code' => 'PLN', 'name' => 'złoty', 'symbol' => 'zł'], $this->generator->payment()->localCurrency());
        self::assertSame(
            PlPlGenerator::create(seed: 5)->payment()->priceInMinorUnits(1, 9, 'PLN'),
            PlPlGenerator::create(seed: 5)->payment()->priceInMinorUnits(1, 9),
        );
    }

    public function testLocalFormats(): void
    {
        $date = new DateTimeImmutable('2026-01-05 07:30');
        $dateTime = $this->generator->dateTime();

        self::assertSame('05.01.2026', $dateTime->localDate($date));
        self::assertSame('05.01.2026 07:30', $dateTime->localDateTime($date));
        self::assertSame('5 stycznia 2026', $dateTime->longDate($date));
        self::assertSame('styczeń', $dateTime->monthName($date));
        self::assertSame('poniedziałek', $dateTime->dayOfWeek($date));
        self::assertSame('Europe/Warsaw', $dateTime->timezone());

        $payment = $this->generator->payment();
        self::assertSame("1\u{00A0}234\u{00A0}567,50\u{00A0}zł", $payment->formatPrice(1234567.5));
        self::assertSame("-0,99\u{00A0}zł", $payment->formatPrice(-0.99));
        self::assertMatchesRegularExpression('/^\d{2},\d{2}\x{00A0}zł$/u', $payment->localPrice(10, 99));
    }
}
