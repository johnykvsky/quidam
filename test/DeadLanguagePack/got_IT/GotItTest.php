<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\got_IT;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;
use Quidam\DeadLanguagePack\got_IT\Address;
use Quidam\DeadLanguagePack\got_IT\Company;
use Quidam\DeadLanguagePack\got_IT\GotItProviderPack;
use Quidam\DeadLanguagePack\got_IT\Person;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use ReflectionClassConstant;

final class GotItTest extends TestCase
{
    private const int REPEAT = 200;

    /** A capitalized Gothic word */
    private const string WORD = '\p{Lu}\p{Ll}+';

    /** Gothic letters and numerals - U+10330 to U+1034A */
    private const string GOTHIC = '[\x{10330}-\x{1034A}]';

    private Quidam $generator;

    protected function setUp(): void
    {
        $this->generator = Quidam::create()->withProvider(new GotItProviderPack());
    }

    public function testPluggingIn(): void
    {
        self::assertInstanceOf(Person::class, $this->generator->person());
        self::assertInstanceOf(Address::class, $this->generator->address());
        self::assertInstanceOf(Company::class, $this->generator->company());
        self::assertSame('got', $this->generator->ext(LocaleInterface::class)->languageCode());
        self::assertSame('Europe/Rome', $this->generator->dateTime()->timezone());
        self::assertMatchesRegularExpression('/^[\d.]+,\d{2}\x{00A0}€$/u', $this->generator->payment()->localPrice(10, 5000));
    }

    /** @return iterable<string, array{string, string}> */
    public static function genitives(): iterable
    {
        yield 'strong name in -s' => ['Ataulfs', 'Ataulfis'];
        yield '-reiks' => ['Þiudareiks', 'Þiudareikis'];
        yield 'weak name in -a' => ['Wulfila', 'Wulfilins'];
        yield 'other names' => ['Bob', 'Bobis'];
    }

    #[DataProvider('genitives')]
    public function testGenitive(string $name, string $genitive): void
    {
        self::assertSame($genitive, $this->generator->ext(Person::class)->genitive($name));
    }

    public function testEveryMaleNameIsStrongOrWeak(): void
    {
        /** @var list<string> $names */
        $names = new ReflectionClassConstant(Person::class, 'FIRST_NAME_MALE')->getValue();

        self::assertSame([], array_values(array_filter($names, static fn (string $name): bool => preg_match('/[sa]$/', $name) !== 1)));
    }

    public function testNamesWithTheFather(): void
    {
        $person = $this->generator->person();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . ' sa ' . self::WORD . '(?:is|ins)$/u', $person->name(Gender::Male));
            self::assertMatchesRegularExpression('/^' . self::WORD . ' so ' . self::WORD . '(?:is|ins)$/u', $person->name(Gender::Female));
        }

        self::assertMatchesRegularExpression('/^[a-z0-9._]+@/', $this->generator->internet()->email('Þiudareiks', 'sa Þiudameris'), 'ASCII emails');
    }

    public function testGothicAlphabet(): void
    {
        $person = $this->generator->ext(Person::class);

        self::assertSame('𐍅𐌿𐌻𐍆𐌹𐌻𐌰', $person->gothic('Wulfila'));
        self::assertSame('𐌸𐌹𐌿𐌳𐌰𐍂𐌴𐌹𐌺𐍃 𐍃𐌰 𐌸𐌹𐌿𐌳𐌰𐌼𐌴𐍂𐌹𐍃', $person->gothic('Þiudareiks sa Þiudameris'));
        self::assertSame('𐍈𐌰𐍃', $person->gothic('Ƕas?'));

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::GOTHIC . '+(?: ' . self::GOTHIC . '+)+$/u', $person->gothic());
        }
    }

    /** @return iterable<string, array{int, string}> */
    public static function numerals(): iterable
    {
        yield 'one' => [1, '·𐌰·'];
        yield 'nine is þ' => [9, '·𐌸·'];
        yield 'twelve' => [12, '·𐌹𐌱·'];
        yield 'ninety' => [90, '·𐍁·'];
        yield 'hundreds, tens and units' => [444, '·𐍅𐌼𐌳·'];
        yield 'nine hundred' => [900, '·𐍊·'];
        yield 'too large' => [1000, '1000'];
        yield 'zero' => [0, '0'];
    }

    #[DataProvider('numerals')]
    public function testGothicNumerals(int $number, string $numeral): void
    {
        self::assertSame($numeral, $this->generator->ext(Address::class)->numeral($number));
    }

    public function testCompanyNames(): void
    {
        $company = $this->generator->company();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $name = $company->company();
            self::assertMatchesRegularExpression('/^' . self::WORD . ' (?:jah \p{L}+(?: is)?|\p{L}+)$/u', $name);
            self::assertDoesNotMatchRegularExpression('/^(\p{L}+) jah \1$/u', $name, 'two different people');
        }
    }

    public function testAddressesAreRealPlaces(): void
    {
        $address = $this->generator->address();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/^' . self::WORD . '(?: ' . self::WORD . ')? ·' . self::GOTHIC . '+·, ' . self::WORD . '(?: \p{L}+)*$/u', $address->address());

            $data = $address->addressData('SP');
            self::assertSame('Spania', $data['state']);
            self::assertMatchesRegularExpression('/^gawi ' . self::WORD . '(?:is|ins)$/u', $data['county']);
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

        $code = new Application()->run(['quidam', 'person.name', '-l', 'got_IT', '-n', '3', '-s', '2026'], $stdout, $stderr);
        rewind($stdout);

        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim((string) stream_get_contents($stdout))));
    }
}
