<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\la_VA;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\LanguagePack\la_VA\Address;
use Quidam\LanguagePack\la_VA\Company;
use Quidam\LanguagePack\la_VA\DateTime;
use Quidam\LanguagePack\la_VA\LaVaProviderPack;
use Quidam\LanguagePack\la_VA\Person;
use Quidam\Quidam;

final class LaVaTest extends TestCase
{
    private const int REPEAT = 200;

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new LaVaProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('la', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/Vatican', $this->generator->dateTime()->timezone());
        self::assertStringStartsWith('VA', $this->generator->payment()->iban());
    }

    public function testTriaNominaForMenFeminineNomenForWomen(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][a-z]+ius [A-Z][a-z]+$/', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][a-z]+ia$/', $person->name(Gender::Female));
        }
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^(?:[A-Z][a-z]+ius et (?:Filii|Socii|Fratres|Liberti|[A-Z][a-z]+ius)|[A-Z][a-z]+ [A-Z][a-z]+ii)$/', $name);
            self::assertDoesNotMatchRegularExpression('/^(\w+) et \1$/', $name, 'two different gentes');
        }
    }

    public function testAddressesAreRealPlacesWithRomanNumbers(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+(?: [A-Z][a-z]+)? [CLXVI]+, [A-Z][a-z]+(?: [A-Z][a-z]+)?$/', $address->address());

            $data = $address->addressData('BR');
            self::assertSame('Britannia', $data['state']);
            self::assertGreaterThan(50.0, $data['latitude']);
            self::assertStringStartsWith('tribus ', $data['county']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    /** @return iterable<string, array{string, string}> */
    public static function romanDates(): iterable
    {
        yield 'Kalends' => ['2026-01-01', 'Kal. Ian. MMXXVI'];
        yield 'before the Nones' => ['2026-03-05', 'a.d. III Non. Mart. MMXXVI'];
        yield 'Nones of a short month' => ['2026-04-05', 'Non. Apr. MMXXVI'];
        yield 'day before the Ides' => ['2026-03-14', 'prid. Id. Mart. MMXXVI'];
        yield 'Ides of March' => ['2026-03-15', 'Id. Mart. MMXXVI'];
        yield 'after the Ides' => ['2026-03-16', 'a.d. XVII Kal. Apr. MMXXVI'];
        yield 'Ides of a short month' => ['2026-10-12', 'a.d. IV Id. Oct. MMXXVI'];
        yield 'into next year' => ['2026-12-31', 'prid. Kal. Ian. MMXXVI'];
        yield 'Terminalia' => ['2024-02-23', 'a.d. VII Kal. Mart. MMXXIV'];
        yield 'leap day' => ['2024-02-24', 'a.d. bis VI Kal. Mart. MMXXIV'];
        yield 'after leap day' => ['2024-02-25', 'a.d. VI Kal. Mart. MMXXIV'];
        yield 'end of leap February' => ['2024-02-29', 'prid. Kal. Mart. MMXXIV'];
        yield 'end of common February' => ['2026-02-28', 'prid. Kal. Mart. MMXXVI'];
    }

    #[DataProvider('romanDates')]
    public function testRomanCalendar(string $date, string $expected): void
    {
        self::assertSame($expected, $this->generator->dateTime()->longDate(new DateTimeImmutable($date)));
    }

    public function testLatinNames(): void
    {
        $date = new DateTimeImmutable('2026-10-12');

        self::assertSame('October', $this->generator->dateTime()->monthName($date));
        self::assertSame('dies Lunae', $this->generator->dateTime()->dayOfWeek($date));
        self::assertSame('12/10/2026', $this->generator->dateTime()->localDate($date));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'la_VA', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
