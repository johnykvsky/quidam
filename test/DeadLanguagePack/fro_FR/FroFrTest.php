<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\fro_FR;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\fro_FR\Address;
use Quidam\DeadLanguagePack\fro_FR\Company;
use Quidam\DeadLanguagePack\fro_FR\DateTime;
use Quidam\DeadLanguagePack\fro_FR\FroFrProviderPack;
use Quidam\DeadLanguagePack\fro_FR\Payment;
use Quidam\DeadLanguagePack\fro_FR\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class FroFrTest extends TestCase
{
    private const int REPEAT = 200;

    /** A capitalized Old French word */
    private const string WORD = '\p{Lu}\p{Ll}+';

    /** Place bynames - the same for both genders */
    private const string PLACE = '(?:de |d\'|du )[\p{L} ]+';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new FroFrProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertInstanceOf(Payment::class, $this->generator->payment());
        self::assertSame('fro', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/Paris', $this->generator->dateTime()->timezone());
    }

    public function testBynamesFollowGender(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:le ' . self::WORD . '|' . self::PLACE . ')$/u', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:la ' . self::WORD . '|' . self::PLACE . ')$/u', $person->name(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Heloïs', 'd\'Orliens'), 'ASCII emails');
    }

    /** @return iterable<string, array{string, string}> */
    public static function charterDates(): iterable
    {
        yield 'day of the month' => ['2026-10-12', 'le XII jor d\'octobre, l\'an de grace deus mil et XXVI'];
        yield 'first day' => ['1007-04-01', 'le premier jor d\'avril, l\'an de grace mil et VII'];
        yield 'feast' => ['1260-11-11', 'a la feste Saint Martin, l\'an de grace mil CC et LX'];
        yield 'Strasbourg Oaths' => ['0842-02-14', 'le XIV jor de fevrier, l\'an de grace DCCC et XLII'];
        yield 'round thousand' => ['2000-08-03', 'le III jor d\'aoust, l\'an de grace deus mil'];
        yield 'hundreds without rest' => ['1300-12-25', 'a Noël, l\'an de grace mil CCC'];
    }

    #[DataProvider('charterDates')]
    public function testCharterDates(string $date, string $longDate): void
    {
        self::assertSame($longDate, $this->generator->dateTime()->longDate(new DateTimeImmutable($date)));
    }

    public function testOldFrenchMonthsAndDays(): void
    {
        $dateTime = $this->generator->dateTime();

        self::assertSame('juignet', $dateTime->monthName(new DateTimeImmutable('2026-07-12')));
        self::assertSame('diemenche', $dateTime->dayOfWeek(new DateTimeImmutable('2026-10-18')));
        self::assertSame('12/10/2026', $dateTime->localDate(new DateTimeImmutable('2026-10-12')));
    }

    /** @return iterable<string, array{float, string}> */
    public static function prices(): iterable
    {
        yield 'livres, sous and deniers' => [3.3334, 'III livres VI sous VIII deniers tornois'];
        yield 'one sol' => [0.05, 'I sol tornois'];
        yield 'one denier' => [1 / 240, 'I denier tornois'];
        yield 'nothing' => [0.001, 'neant'];
        yield 'debt' => [-1.05, '-I livre I sol tornois'];
        yield 'a large sum' => [1444.0, 'MCDXLIV livres tornois'];
    }

    #[DataProvider('prices')]
    public function testLivresSousAndDeniers(float $amount, string $expected): void
    {
        self::assertSame($expected, $this->generator->payment()->formatPrice($amount));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^(?:le mestier des ' . self::WORD . '|' . self::WORD . ' .+ (?:et si \p{Ll}+|et .+))$/u', $company->company());
        }
    }

    public function testAddressesWithSigns(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[\p{L} ]+, a l\'ensaigne (?:du |de la |de l\'|des )[\p{L}\' ]+, ' . self::WORD . '(?: ' . self::WORD . ')?$/u', $address->address());

            $data = $address->addressData('CH');
            self::assertSame('Champaigne', $data['state']);
            self::assertStringStartsWith('la parroisse ', $data['county']);
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

        $code = new Application()->run(['quidam', 'person.name', '-l', 'fro_FR', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
