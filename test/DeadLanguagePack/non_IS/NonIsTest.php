<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\non_IS;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\non_IS\Address;
use Quidam\DeadLanguagePack\non_IS\Company;
use Quidam\DeadLanguagePack\non_IS\DateTime;
use Quidam\DeadLanguagePack\non_IS\NonIsProviderPack;
use Quidam\DeadLanguagePack\non_IS\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use ReflectionClassConstant;

final class NonIsTest extends TestCase
{
    private const int REPEAT = 200;

    /** A capitalized Old Norse word */
    private const string WORD = '\p{Lu}\p{Ll}+';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new NonIsProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('non', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Atlantic/Reykjavik', $this->generator->dateTime()->timezone());
        self::assertMatchesRegularExpression('/^[\d.]+\x{00A0}kr\.$/u', $this->generator->payment()->localPrice(10, 5000), 'ISK - no decimals');
    }

    public function testEveryMaleNameHasGenitive(): void
    {
        /** @var list<string> $names */
        $names = new ReflectionClassConstant(Person::class, 'FIRST_NAME_MALE')->getValue();
        /** @var array<string, string> $genitives */
        $genitives = new ReflectionClassConstant(Person::class, 'GENITIVE')->getValue();
        $person = $this->generator->ext(Person::class);

        self::assertSame([], array_values(array_diff($names, array_keys($genitives))));
        self::assertSame('Bjarnar', $person->genitive('Bjǫrn'));
        self::assertSame('Sigurðar', $person->genitive('Sigurðr'));
        self::assertSame('Bobs', $person->genitive('Bob'), 'unknown names get "s"');
    }

    public function testPatronymics(): void
    {
        $person = $this->generator->ext(Person::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . ' ' . self::WORD . 'son$/u', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^' . self::WORD . ' ' . self::WORD . 'dóttir$/u', $person->name(Gender::Female));
            self::assertMatchesRegularExpression('/^(?:in|inn) \p{Ll}+$|^\p{Ll}+$/u', $person->byname(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Hǫskuldr', 'Þorsteinsson'), 'ASCII emails');
    }

    public function testRunes(): void
    {
        $person = $this->generator->ext(Person::class);

        self::assertSame('ᛚᛁᚠᚱ᛫ᛁᚱᛁᚴᛋᚢᚾ', $person->runes('Leifr Eiríksson'));
        self::assertSame('ᚼᛅᚱᛅᛚᛏᚱ᛫ᛒᛚᛅᛏᚢᚾ', $person->runes('Haraldr blátǫnn'));
        self::assertSame('ᚦᚢᚱ', $person->runes('Þórr!'));

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[\x{16A0}-\x{16F8}]+$/u', $person->runes());
        }
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:ok \p{L}+|\p{L}+)$/u', $name);
            self::assertDoesNotMatchRegularExpression('/^(\p{L}+) ok \1$/u', $name, 'two different people');
        }
    }

    public function testAddressesAreRealPlaces(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . '(?: \p{Ll}+)? [1-9]\d?, ' . self::WORD . '$/u', $address->address());

            $data = $address->addressData('IR');
            self::assertSame('Írland', $data['state']);
            self::assertStringEndsWith('þing', strtr($data['county'], ['Þingvǫllr' => 'þing']));
            self::assertMatchesRegularExpression('/^[1-9]\d{2}$/', $data['postcode']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function norseDates(): iterable
    {
        yield 'first day of summer - Thursday 19-25 April' => ['2026-04-23', '1. Hǫrpu 2026', 'Harpa'];
        yield 'last day of winter' => ['2026-04-22', '30. Einmánaðar 2026', 'Einmánuðr'];
        yield 'extra nights after Sólmánuðr' => ['2026-07-22', '1. aukanátta 2026', 'aukanætr'];
        yield 'Heyannir starts on a Sunday' => ['2026-07-26', '1. Heyanna 2026', 'Heyannir'];
        yield 'first day of winter - a Saturday' => ['2026-10-24', '1. Gormánaðar 2026', 'Gormánuðr'];
        yield 'Þorri starts on a Friday' => ['2027-01-22', '1. Þorra 2027', 'Þorri'];
        yield 'Gói starts on a Sunday' => ['2027-02-21', '1. Góu 2027', 'Gói'];
        yield 'leap week of 2023' => ['2023-07-27', '5. sumarauka 2023', 'sumarauki'];
        yield 'winter a week later after a leap week' => ['2023-10-28', '1. Gormánaðar 2023', 'Gormánuðr'];
    }

    #[DataProvider('norseDates')]
    public function testOldIcelandicCalendar(string $date, string $longDate, string $month): void
    {
        $date = new DateTimeImmutable($date);

        self::assertSame($longDate, $this->generator->dateTime()->longDate($date));
        self::assertSame($month, $this->generator->dateTime()->monthName($date));
    }

    public function testSeasonsAndMonthsKeepTheirWeekdays(): void
    {
        $dateTime = $this->generator->dateTime();
        // first days of months fall on fixed weekdays - 30-day months move the weekday by two
        $weekdays = [
            'Harpa' => 'Thu', 'Skerpla' => 'Sat', 'Sólmánuðr' => 'Mon', 'aukanætr' => 'Wed', 'Heyannir' => 'Sun',
            'Tvímánuðr' => 'Tue', 'Haustmánuðr' => 'Thu', 'Gormánuðr' => 'Sat', 'Ýlir' => 'Mon', 'Mǫrsugr' => 'Wed',
            'Þorri' => 'Fri', 'Gói' => 'Sun', 'Einmánuðr' => 'Tue',
        ];
        $previous = $dateTime->monthName(new DateTimeImmutable('1999-12-31'));

        for ($date = new DateTimeImmutable('2000-01-01'); $date < new DateTimeImmutable('2040-01-01'); $date = $date->modify('+1 day')) {
            $month = $dateTime->monthName($date);

            if ($month !== $previous && $month !== 'sumarauki' && $previous !== 'sumarauki') {
                self::assertSame($weekdays[$month], $date->format('D'), $month . ' starting ' . $date->format('Y-m-d'));
            }

            $previous = $month;
        }
    }

    public function testNorseDayNames(): void
    {
        self::assertSame('óðinsdagr', $this->generator->dateTime()->dayOfWeek(new DateTimeImmutable('2026-10-14')));
        self::assertSame('12.10.2026', $this->generator->dateTime()->localDate(new DateTimeImmutable('2026-10-12')));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'non_IS', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
