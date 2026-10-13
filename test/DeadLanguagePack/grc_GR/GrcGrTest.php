<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\grc_GR;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\grc_GR\Address;
use Quidam\DeadLanguagePack\grc_GR\Company;
use Quidam\DeadLanguagePack\grc_GR\DateTime;
use Quidam\DeadLanguagePack\grc_GR\GrcGrProviderPack;
use Quidam\DeadLanguagePack\grc_GR\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use ReflectionClassConstant;

final class GrcGrTest extends TestCase
{
    private const int REPEAT = 200;

    /** Greek numeral letters with the numeral sign */
    private const string NUMERAL = '͵?[αβγδεϛζηθικλμνξοπϟρστυφχψωϡ]+ʹ';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new GrcGrProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('grc', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/Athens', $this->generator->dateTime()->timezone());
        self::assertStringStartsWith('GR', $this->generator->payment()->iban());
    }

    public function testEveryMaleNameHasGenitive(): void
    {
        /** @var list<string> $names */
        $names = new ReflectionClassConstant(Person::class, 'FIRST_NAME_MALE')->getValue();
        /** @var array<string, string> $genitives */
        $genitives = new ReflectionClassConstant(Person::class, 'GENITIVE')->getValue();
        $person = $this->generator->ext(Person::class);

        self::assertSame([], array_values(array_diff($names, array_keys($genitives))));
        self::assertSame('Sophroniskou', $person->genitive('Sophroniskos'));
        self::assertSame('Xenophontos', $person->genitive('Xenophon'));
        self::assertSame('Bob', $person->genitive('Bob'), 'unknown names unchanged');
    }

    public function testPatronymicAndDeme(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][a-z]+(?:ou|os|ous|eous) [A-Z][a-z]+$/', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][a-z]+(?:ou|os|ous|eous)$/', $person->name(Gender::Female));
        }
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^(?:[A-Z][a-z]+ kai [A-Z][a-z]+|[A-Z][a-z]+ [A-Z][a-z]+(?:ou|os|ous|eous))$/', $name);
            self::assertDoesNotMatchRegularExpression('/^(\w+) kai \1$/', $name, 'two different people');
        }
    }

    public function testAddressesAreRealPlacesWithGreekNumbers(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+(?: [a-z]+)?(?: [A-Z][a-z]+)? ' . self::NUMERAL . ', [A-Z][a-z]+$/u', $address->address());

            $data = $address->addressData('SI');
            self::assertSame('Sikelia', $data['state']);
            self::assertStringStartsWith('phyle ', $data['county']);
            self::assertMatchesRegularExpression('/^[1-9]\d{2} \d{2}$/', $data['postcode']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function atticDates(): iterable
    {
        // new moons (UTC): 2026-06-15 02:54, 2026-07-14 09:44, 2026-10-10 15:50, 2025-12-20 01:43
        yield 'Hekatombaion 1 - first month after the solstice' => ['2026-07-16', 'αʹ Hekatombaionos, Ol. ψαʹ, etos βʹ', 'Hekatombaion'];
        yield 'last day of the year before' => ['2026-07-15', 'λʹ Skirophorionos, Ol. ψαʹ, etos αʹ', 'Skirophorion'];
        yield 'Pyanepsion' => ['2026-10-12', 'αʹ Pyanepsionos, Ol. ψαʹ, etos βʹ', 'Pyanepsion'];
        yield 'second Poseideon of a 13-month year' => ['2026-01-01', 'ιβʹ Poseideonos deuterou, Ol. ψαʹ, etos αʹ', 'Poseideon deuteros'];
        yield 'Gamelion after it' => ['2026-01-20', 'αʹ Gamelionos, Ol. ψαʹ, etos αʹ', 'Gamelion'];
        // astronomical year -398 is 399 BC (proleptic Gregorian)
        yield 'death of Sokrates, spring 399 BC - Olympiad 95, year 1' => ['-0398-05-20', 'ιβʹ Thargelionos, Ol. ϟεʹ, etos αʹ', 'Thargelion'];
        yield 'year 1 AD - Olympiad 195' => ['0001-08-01', 'ιαʹ Metageitnionos, Ol. ρϟεʹ, etos αʹ', 'Metageitnion'];
    }

    #[DataProvider('atticDates')]
    public function testAtticCalendar(string $date, string $longDate, string $month): void
    {
        $date = new DateTimeImmutable($date);

        self::assertSame($longDate, $this->generator->dateTime()->longDate($date));
        self::assertSame($month, $this->generator->dateTime()->monthName($date));
    }

    public function testAtticMonthsInOrder(): void
    {
        $dateTime = $this->generator->dateTime();
        $months = [];
        $previous = null;

        for ($date = new DateTimeImmutable('2025-07-01'); $date < new DateTimeImmutable('2027-07-31'); $date = $date->modify('+1 day')) {
            $month = $dateTime->monthName($date);
            $day = $dateTime->longDate($date);
            self::assertMatchesRegularExpression('/^' . self::NUMERAL . ' /u', $day);

            if ($month !== $previous) {
                if ($previous !== null) {
                    self::assertStringStartsWith('αʹ ', $day, 'months start on the 1st: ' . $date->format('Y-m-d'));
                }

                $months[] = $month;
                $previous = $month;
            }
        }

        self::assertSame([
            'Hekatombaion', 'Metageitnion', 'Boedromion', 'Pyanepsion', 'Maimakterion', 'Poseideon', 'Poseideon deuteros',
            'Gamelion', 'Anthesterion', 'Elaphebolion', 'Mounichion', 'Thargelion', 'Skirophorion',
            'Hekatombaion', 'Metageitnion', 'Boedromion', 'Pyanepsion', 'Maimakterion', 'Poseideon', 'Gamelion',
            'Anthesterion', 'Elaphebolion', 'Mounichion', 'Thargelion', 'Skirophorion',
            'Hekatombaion',
        ], $months, '13 months in 2025/26, 12 in 2026/27');
    }

    public function testGreekNames(): void
    {
        $date = new DateTimeImmutable('2026-10-12');

        self::assertSame('hemera Selenes', $this->generator->dateTime()->dayOfWeek($date));
        self::assertSame('12/10/2026', $this->generator->dateTime()->localDate($date));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'grc_GR', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
