<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\enm_GB;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\enm_GB\Address;
use Quidam\DeadLanguagePack\enm_GB\Company;
use Quidam\DeadLanguagePack\enm_GB\DateTime;
use Quidam\DeadLanguagePack\enm_GB\EnmGbProviderPack;
use Quidam\DeadLanguagePack\enm_GB\Payment;
use Quidam\DeadLanguagePack\enm_GB\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class EnmGbTest extends TestCase
{
    private const int REPEAT = 200;

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new EnmGbProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertInstanceOf(Payment::class, $this->generator->payment());
        self::assertSame('enm', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/London', $this->generator->dateTime()->timezone());
    }

    public function testGivenNameAndByname(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ (?:(?:le|atte|de|of|Fitz) )?[A-Z][a-z]+$/', $person->name(Gender::Female));
        }
    }

    /** @return iterable<string, array{float, string}> */
    public static function prices(): iterable
    {
        yield 'pounds, shillings and pence' => [3.3334, 'iij li. vj s. viij d.'];
        yield 'half a pound' => [0.5, 'x s.'];
        yield 'a penny' => [1 / 240, 'j d.'];
        yield 'nothing' => [0.001, 'nought'];
        yield 'debt' => [-1.05, '-j li. j s.'];
        yield 'a large sum' => [1444.0, 'mcdxliiij li.'];
    }

    #[DataProvider('prices')]
    public function testPoundsShillingsAndPence(float $amount, string $expected): void
    {
        self::assertSame($expected, $this->generator->payment()->formatPrice($amount));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^(?:the Mistere of [A-Z][a-z]+|[A-Z][A-Za-z ]+ and [A-Za-z ]+)$/', $company->company());
        }
    }

    public function testAddressesWithSigns(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+(?: lane)?, atte signe of the [A-Z][a-z]+(?: [A-Z][a-z]+)?, [A-Z][a-z]+$/', $address->address());

            $data = $address->addressData('KE');
            self::assertSame('Kent', $data['state']);
            self::assertStringStartsWith('the parisshe of Seint ', $data['county']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    /** @return iterable<string, array{string, string}> */
    public static function dates(): iterable
    {
        yield 'day of the month' => ['2026-10-12', 'the xij day of Octobre, the v yeer of Kyng Charles the Thridde'];
        yield 'feast' => ['2026-09-29', 'Michelmasse, the v yeer of Kyng Charles the Thridde'];
        yield 'last day of a reign' => ['2022-09-07', 'the vij day of Septembre, the lxxj yeer of Quene Elizabeth the Seconde'];
        yield 'first day of a reign' => ['2022-09-08', 'the viij day of Septembre, the j yeer of Kyng Charles the Thridde'];
        yield 'short reign' => ['1936-06-01', 'the j day of Juyn, the j yeer of Kyng Edward the Eighte'];
        yield 'before Victoria - year of our Lord' => ['1387-04-17', 'the xvij day of Aprill, the yeer of oure Lord mccclxxxvij'];
    }

    #[DataProvider('dates')]
    public function testCharterDates(string $date, string $expected): void
    {
        self::assertSame($expected, $this->generator->dateTime()->longDate(new DateTimeImmutable($date)));
    }

    public function testMiddleEnglishNames(): void
    {
        self::assertSame('Aprill', $this->generator->dateTime()->monthName(new DateTimeImmutable('2026-04-01')));
        self::assertSame('Sonday', $this->generator->dateTime()->dayOfWeek(new DateTimeImmutable('2026-10-11')));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'enm_GB', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
