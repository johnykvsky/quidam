<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\ang_GB;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\ang_GB\Address;
use Quidam\DeadLanguagePack\ang_GB\AngGbProviderPack;
use Quidam\DeadLanguagePack\ang_GB\Company;
use Quidam\DeadLanguagePack\ang_GB\DateTime;
use Quidam\DeadLanguagePack\ang_GB\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class AngGbTest extends TestCase
{
    private const int REPEAT = 200;

    private const string FIRST = '(?:Ælf|Æþel|Beorht|Cyne|Cūþ|Ēad|Ecg|God|Here|Lēof|Ōs|Sige|Wīg|Wulf)';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new AngGbProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('ang', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/London', $this->generator->dateTime()->timezone());
        self::assertSame('£1,234.50', $this->generator->payment()->formatPrice(1234.5));
    }

    public function testTwoElementNamesAndFathers(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $male = $person->name(Gender::Male);
            self::assertMatchesRegularExpression('/^' . self::FIRST . '\p{Ll}+ ' . self::FIRST . '\p{Ll}+ing$/u', $male);
            self::assertDoesNotMatchRegularExpression('/^(?:Wulfwulf|Herehere)/u', $male, 'never one element twice');
            self::assertMatchesRegularExpression('/^' . self::FIRST . '(?:burh|flǣd|gifu|hild|rūn|swīþ|þrȳþ|wynn) ' . self::FIRST . '\p{Ll}+s dohtor$/u', $person->name(Gender::Female));
        }

        self::assertSame('Ælfrēdes', $this->generator->ext(Person::class)->genitive('Ælfrēd'));
        self::assertSame('Godwines', $this->generator->ext(Person::class)->genitive('Godwine'));
        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Æþelflǣd', 'Ēadgāres dohtor'), 'ASCII emails');
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^(?:\p{L}+ and (?:his \p{L}+|\p{L}+)|\p{L}+ \p{L}+e?s)$/u', $name);
            self::assertDoesNotMatchRegularExpression('/^(\p{L}+) and \1$/u', $name, 'two different people');
        }
    }

    public function testAddressesWithShires(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^\p{Lu}[\p{L} ]+ \p{Ll}+, \p{Lu}\p{Ll}+$/u', $address->address());

            $data = $address->addressData('ME');
            self::assertSame('Mierce', $data['state']);
            self::assertNotSame('', $data['county']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        self::assertSame(['Bernice', 'Dere'], self::sorted(array_map(static fn (): string => $address->cityData('NH')['county'], range(1, 100))), 'the north had no shires');

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    public function testOldEnglishDates(): void
    {
        $dateTime = $this->generator->dateTime();

        self::assertSame('12 Winterfylleþ, anno mmxxvi', $dateTime->longDate(new DateTimeImmutable('2026-10-12')));
        self::assertSame('25 Ǣrra Gēola, anno dccclxxviii', $dateTime->longDate(new DateTimeImmutable('0878-12-25')));
        self::assertSame('Hrēþmōnaþ', $dateTime->monthName(new DateTimeImmutable('2026-03-01')));
        self::assertSame('Wōdnesdæg', $dateTime->dayOfWeek(new DateTimeImmutable('2026-10-14')));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'ang_GB', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }
}
