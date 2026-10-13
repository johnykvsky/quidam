<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\akk_IQ;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\akk_IQ\Address;
use Quidam\DeadLanguagePack\akk_IQ\AkkIqProviderPack;
use Quidam\DeadLanguagePack\akk_IQ\Company;
use Quidam\DeadLanguagePack\akk_IQ\DateTime;
use Quidam\DeadLanguagePack\akk_IQ\Payment;
use Quidam\DeadLanguagePack\akk_IQ\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class AkkIqTest extends TestCase
{
    private const int REPEAT = 200;

    /** An Akkadian name - capitalized parts joined by hyphens: "Sîn-muballiṭ", "Nabû-kudurri-uṣur" */
    private const string NAME = '\p{Lu}\p{Ll}*(?:-\p{L}+)*';

    /** Cuneiform signs - U+12000 to U+123FF */
    private const string CUNEIFORM = '[\x{12000}-\x{123FF}]';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new AkkIqProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(DateTime::class, $this->generator->dateTime());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertInstanceOf(Payment::class, $this->generator->payment());
        self::assertSame('akk', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Asia/Baghdad', $this->generator->dateTime()->timezone());
    }

    public function testNamesWithTheFather(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::NAME . ' mār ' . self::NAME . '$/u', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^' . self::NAME . ' mārat ' . self::NAME . '$/u', $person->name(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Ḫammurabi', 'mār Sîn-muballiṭ'), 'ASCII emails');
    }

    /** @return iterable<string, array{string, string}> */
    public static function syllables(): iterable
    {
        yield 'ša-ar-ru-ki-in' => ['Šarru-kīn', '𒊭𒅈𒊒𒆠𒅔'];
        yield 'ḫa-am-mu-ra-bi' => ['Ḫammurabi', '𒄩𒄠𒈬𒊏𒁉'];
        yield 'i-li-id-di-na-am' => ['Ilī-iddinam', '𒄿𒇷𒀉𒁲𒈾𒄠'];
        yield 'en-ḫe-du-an-na - a closed first syllable is one sign' => ['Enḫeduanna', '𒂗𒄭𒁺𒀭𒈾'];
        yield 'aš-šur-ba-ni-ap-li' => ['Aššur-bāni-apli', '𒀸𒋗𒌨𒁀𒉌𒀊𒇷'];
        yield 'aleph dropped' => ['Duʾūzu', '𒁺𒌑𒍪'];
        yield 'words and letters without a sign' => ['Gilgameš, ox um!', '𒄀𒅋𒂵𒈨𒅖 𒌝'];
    }

    #[DataProvider('syllables')]
    public function testCuneiform(string $text, string $signs): void
    {
        self::assertSame($signs, $this->generator->ext(Person::class)->cuneiform($text));
    }

    public function testCuneiformOfRandomNames(): void
    {
        $person = $this->generator->ext(Person::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::CUNEIFORM . '+(?: ' . self::CUNEIFORM . '+){2}$/u', $person->cuneiform());
        }
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function babylonianDates(): iterable
    {
        yield 'first crescent after the new moon of 17 April' => ['2026-04-18', 'ūmum 1 ša Nisanni, šanat 2337', 'Nisannu'];
        yield 'last day of the year' => ['2026-04-17', 'ūmum 30 ša Addari arkî, šanat 2336', 'Addaru arkû'];
        yield 'second Addaru of a thirteen-month year' => ['2026-03-19', 'ūmum 1 ša Addari arkî, šanat 2336', 'Addaru arkû'];
        yield 'autumn' => ['2026-10-13', 'ūmum 2 ša Tašrīti, šanat 2337', 'Tašrītu'];
        yield 'January belongs to the year before' => ['2027-01-08', 'ūmum 1 ša Ṭebēti, šanat 2337', 'Ṭebētu'];
        yield 'twelve-month year' => ['2027-04-07', 'ūmum 1 ša Nisanni, šanat 2338', 'Nisannu'];
    }

    #[DataProvider('babylonianDates')]
    public function testBabylonianCalendar(string $date, string $longDate, string $month): void
    {
        $date = new DateTimeImmutable($date);

        self::assertSame($longDate, $this->generator->dateTime()->longDate($date));
        self::assertSame($month, $this->generator->dateTime()->monthName($date));
    }

    public function testLunarMonthsAndLeapYears(): void
    {
        $dateTime = $this->generator->dateTime();
        $lengths = [];
        $leapMonths = 0;
        $length = 0;

        for ($date = new DateTimeImmutable('2000-01-01'); $date < new DateTimeImmutable('2038-01-01'); $date = $date->modify('+1 day')) {
            if (str_starts_with($dateTime->longDate($date), 'ūmum 1 ')) {
                $lengths[] = $length;
                $length = 0;
                $month = $dateTime->monthName($date);
                $leapMonths += $month === 'Addaru arkû' ? 1 : 0;

                if ($month === 'Nisannu') {
                    // the year begins with the first month starting on or after the equinox
                    self::assertGreaterThanOrEqual('03-20', $date->format('m-d'));
                    self::assertLessThanOrEqual('04-19', $date->format('m-d'));
                }
            }

            ++$length;
        }

        $unique = array_unique(array_slice($lengths, 1));
        sort($unique);
        self::assertSame([29, 30], $unique, 'months of the moon');
        // 7 leap months in 19 years, as the Babylonian cycle had
        self::assertSame(14, $leapMonths);
    }

    public function testPlanetaryDays(): void
    {
        self::assertSame('ūm Nabû', $this->generator->dateTime()->dayOfWeek(new DateTimeImmutable('2026-10-14')));
        self::assertSame('13/10/2026', $this->generator->dateTime()->localDate(new DateTimeImmutable('2026-10-13')));
    }

    /** @return iterable<string, array{float, string}> */
    public static function prices(): iterable
    {
        yield 'minas, shekels and grains' => [1234.5, '2 mana 3 šiqil 81 uṭṭet kaspim'];
        yield 'a shekel' => [10.0, '1 šiqil kaspim'];
        yield 'nothing' => [0.01, 'ul mimma'];
        yield 'debt' => [-25.0, '-2 šiqil 90 uṭṭet kaspim'];
    }

    #[DataProvider('prices')]
    public function testSilverByWeight(float $amount, string $expected): void
    {
        self::assertSame($expected, $this->generator->payment()->formatPrice($amount));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^(?:bīt ' . self::NAME . '|' . self::NAME . ' u (?:\p{Ll}+šu|' . self::NAME . '))$/u', $name);
            self::assertDoesNotMatchRegularExpression('/^(\S+) u \1$/u', $name, 'two different people');
        }
    }

    public function testAddressesWithOwners(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^[\p{L}\- ]+, bīt ' . self::NAME . ', ' . self::NAME . '$/u', $address->address());

            $data = $address->addressData('AS');
            self::assertSame('Aššur', $data['state']);
            self::assertMatchesRegularExpression('/^E\p{Ll}+$/u', $data['county']);
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

        $code = new Application()->run(['quidam', 'person.name', '-l', 'akk_IQ', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
