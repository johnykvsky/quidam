<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\tlh_QO;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\tlh_QO\Address;
use Quidam\DeadLanguagePack\tlh_QO\Company;
use Quidam\DeadLanguagePack\tlh_QO\DateTime;
use Quidam\DeadLanguagePack\tlh_QO\Payment;
use Quidam\DeadLanguagePack\tlh_QO\Person;
use Quidam\DeadLanguagePack\tlh_QO\TlhQoProviderPack;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use ReflectionMethod;

final class TlhQoTest extends TestCase
{
    private const int REPEAT = 200;

    /** A Klingon number up to 999 */
    private const string NUMBER = "(?:(?:wa'|cha'|wej|loS|vagh|jav|Soch|chorgh|Hut)(?:vatlh|maH)? ?)+";

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new TlhQoProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertInstanceOf(Payment::class, $this->generator->payment());
        self::assertSame('tlh', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertNull($this->generator->ext(LocaleInterface::class)->countryCode(), 'no Earth country');
    }

    public function testNamedByTheFather(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression("/^[A-Z][A-Za-z']+, son of [A-Z][A-Za-z']+$/", $person->name(Gender::Male));
            self::assertMatchesRegularExpression("/^[A-Z][A-Za-z']+, daughter of [A-Z][A-Za-z']+$/", $person->name(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email("K'Tarq", 'son of Torvek'), 'ASCII emails');
    }

    public function testDarseks(): void
    {
        self::assertSame(['code' => 'XXX', 'name' => 'Darsek', 'symbol' => 'darsek'], $this->generator->payment()->localCurrency());
        self::assertSame('1,250 darsek', $this->generator->payment()->formatPrice(1250));
        self::assertMatchesRegularExpression('/^[\d,]+ darsek$/', $this->generator->payment()->localPrice(10, 5000));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression("/^(?:[A-Z][A-Za-z']+'s [A-Z].+|House of [A-Z][A-Za-z']+ [A-Z][a-z]+|[A-Z][A-Za-z']+ & [A-Z][A-Za-z']+)$/", $name);
            self::assertDoesNotMatchRegularExpression("/^([\\w']+) & \\1$/", $name, 'two different founders');
        }
    }

    public function testAddressesWithKlingonNumbers(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression("/^[A-Z][A-Za-z' ]+ " . self::NUMBER . ", [A-Z][A-Za-z' ]+$/", $address->address());

            $data = $address->addressData('QO');
            self::assertSame("Qo'noS", $data['state']);
            self::assertStringStartsWith('House of ', $data['county']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    public function testKlingonNumbers(): void
    {
        $number = new ReflectionMethod(Address::class, 'klingonNumber');
        $address = $this->generator->address();

        self::assertSame("wa'", $number->invoke($address, 1));
        self::assertSame("wa'maH", $number->invoke($address, 10));
        self::assertSame("cha'maH wej", $number->invoke($address, 23));
        self::assertSame("wa'vatlh", $number->invoke($address, 100));
        self::assertSame("wa'vatlh cha'maH wej", $number->invoke($address, 123));
        self::assertSame('Hutvatlh HutmaH Hut', $number->invoke($address, 999));
    }

    public function testStardates(): void
    {
        self::assertSame('Stardate 2026.285', $this->generator->dateTime()->longDate(new DateTimeImmutable('2026-10-12')));
        self::assertSame('Stardate 2026.1', $this->generator->dateTime()->longDate(new DateTimeImmutable('2026-01-01')));
        self::assertSame('Stardate 2024.366', $this->generator->dateTime()->longDate(new DateTimeImmutable('2024-12-31')));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'tlh_QO', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
