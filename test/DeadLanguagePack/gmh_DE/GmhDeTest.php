<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\gmh_DE;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\gmh_DE\Address;
use Quidam\DeadLanguagePack\gmh_DE\Company;
use Quidam\DeadLanguagePack\gmh_DE\DateTime;
use Quidam\DeadLanguagePack\gmh_DE\GmhDeProviderPack;
use Quidam\DeadLanguagePack\gmh_DE\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class GmhDeTest extends TestCase
{
    private const int REPEAT = 200;

    /** A capitalized Middle High German word */
    private const string WORD = '\p{Lu}\p{Ll}+';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new GmhDeProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('gmh', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertContains($this->generator->dateTime()->timezone(), ['Europe/Berlin', 'Europe/Busingen']);
    }

    public function testBynamesFollowGender(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:der \p{Lu}\p{Ll}+|von .+|ûf dem Berge|an dem Bache)$/u', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:diu \p{Lu}\p{Ll}+|von .+|ûf dem Berge|an dem Bache)$/u', $person->name(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Rüedegêr', 'von Bechelâren'), 'ASCII emails');
    }

    /** @return iterable<string, array{string, string}> */
    public static function charterDates(): iterable
    {
        yield 'day of the month' => ['2026-10-12', 'an dem 12. tage des Wînmânôdes, dô man zalte von gotes gebürte zwei tûsent sehs und zweinzic jâr'];
        yield 'feast' => ['2026-11-11', 'an sant Martînes tage, dô man zalte von gotes gebürte zwei tûsent sehs und zweinzic jâr'];
        yield 'hundreds and round tens' => ['1260-03-07', 'an dem 7. tage des Lenzemânôdes, dô man zalte von gotes gebürte tûsent zwei hundert sehzic jâr'];
        yield 'teens' => ['1415-05-14', 'an dem 14. tage des Meien, dô man zalte von gotes gebürte tûsent vier hundert vünfzehen jâr'];
        yield 'round thousand' => ['2000-12-25', 'ze wîhennahten, dô man zalte von gotes gebürte zwei tûsent jâr'];
        yield 'hundred alone' => ['1100-02-01', 'an dem 1. tage des Hornunges, dô man zalte von gotes gebürte tûsent hundert jâr'];
    }

    #[DataProvider('charterDates')]
    public function testCharterDates(string $date, string $longDate): void
    {
        self::assertSame($longDate, $this->generator->dateTime()->longDate(new DateTimeImmutable($date)));
    }

    public function testOldGermanMonthsAndDays(): void
    {
        $dateTime = $this->generator->dateTime();

        self::assertSame('Wînmânôt', $dateTime->monthName(new DateTimeImmutable('2026-10-12')));
        self::assertSame('Hornunc', $dateTime->monthName(new DateTimeImmutable('2026-02-12')));
        self::assertSame('mittewoche', $dateTime->dayOfWeek(new DateTimeImmutable('2026-10-14')));
        self::assertSame('12.10.2026', $dateTime->localDate(new DateTimeImmutable('2026-10-12')));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^(?:diu zunft der ' . self::WORD . '|' . self::WORD . ' .+ (?:und sîne \p{Ll}+|unde .+))$/u', $company->company());
        }
    }

    public function testAddressesWithHouseNames(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . '(?: \p{Ll}+)?, hûs ze[mr] (?:\p{Ll}+ )?' . self::WORD . ', ' . self::WORD . '$/u', $address->address());

            $data = $address->addressData('OS');
            self::assertSame('Ôsterrîche', $data['state']);
            self::assertStringStartsWith('bistuom ze ', $data['county']);
            self::assertMatchesRegularExpression('/^\d{5}$/', $data['postcode']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'gmh_DE', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
