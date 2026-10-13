<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\en_XS;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\en_XS\Address;
use Quidam\DeadLanguagePack\en_XS\Company;
use Quidam\DeadLanguagePack\en_XS\DateTime;
use Quidam\DeadLanguagePack\en_XS\EnXsProviderPack;
use Quidam\DeadLanguagePack\en_XS\Payment;
use Quidam\DeadLanguagePack\en_XS\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class EnXsTest extends TestCase
{
    private const int REPEAT = 200;

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new EnXsProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertInstanceOf(Payment::class, $this->generator->payment());
        self::assertSame('GB', $this->generator->ext(LocaleInterface::class)->countryCode(), 'XS only in the pack code');
        self::assertSame('Europe/London', $this->generator->dateTime()->timezone());
    }

    public function testNamesAndInsults(): void
    {
        $person = $this->generator->ext(Person::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][a-z]+$/', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^[A-Z].+[.!]$/', $person->insult());
        }
    }

    /** @return iterable<string, array{float, string}> */
    public static function prices(): iterable
    {
        yield 'pounds, shillings and pence' => [3.3334, '£3 6s. 8d.'];
        yield 'half a pound' => [0.5, '10s.'];
        yield 'a penny' => [1 / 240, '1d.'];
        yield 'nothing' => [0.001, 'nought'];
        yield 'debt' => [-1.05, '-£1 1s.'];
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
            $name = $company->company();
            self::assertMatchesRegularExpression("/^(?:The Lord [A-Z][a-z]+'s (?:Men|Players|Servants)|[A-Z][a-z]+ & [A-Z][a-z]+|The [A-Z][A-Za-z' ]+ Tavern)$/", $name);
            self::assertDoesNotMatchRegularExpression('/^(\w+) & \1$/', $name, 'two different partners');
        }
    }

    public function testAddressesWithSigns(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression("/^[A-Z][A-Za-z' ]+, at the sign of the [A-Z][A-Za-z' ]+, [A-Z][A-Za-z-]+$/", $address->address());

            $data = $address->addressData('IT');
            self::assertSame('Italy', $data['state']);
            self::assertStringStartsWith('the parish of ', $data['county']);
            self::assertSame($data['street'] . ', ' . $data['city'], $data['address']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function oldStyleDates(): iterable
    {
        yield 'Julian - 13 days behind' => ['2026-10-12', 'the nine and twentieth day of September, in the year of our Lord 2026', 'September'];
        yield "Shakespeare's death - 23 April 1616 Old Style" => ['1616-05-03', 'the three and twentieth day of April, in the year of our Lord 1616', 'April'];
        yield 'calendar change of 1752' => ['1752-09-14', 'the third day of September, in the year of our Lord 1752', 'September'];
        yield 'before Lady Day - both years' => ['2026-04-06', 'the four and twentieth day of March, in the year of our Lord 2025/6', 'March'];
        yield 'Lady Day - new year' => ['2026-04-07', 'the five and twentieth day of March, in the year of our Lord 2026', 'March'];
        yield 'Julian 29 February 1900' => ['1900-03-13', 'the nine and twentieth day of February, in the year of our Lord 1899/1900', 'February'];
        yield 'new decade' => ['2030-04-01', 'the nineteenth day of March, in the year of our Lord 2029/30', 'March'];
        yield 'one and thirtieth' => ['2026-01-13', 'the one and thirtieth day of December, in the year of our Lord 2025', 'December'];
    }

    #[DataProvider('oldStyleDates')]
    public function testOldStyle(string $date, string $longDate, string $month): void
    {
        $date = new DateTimeImmutable($date);

        self::assertSame($longDate, $this->generator->dateTime()->longDate($date));
        self::assertSame($month, $this->generator->dateTime()->monthName($date));
    }

    public function testCommandLine(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', 'person.name', '-l', 'en_XS', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
