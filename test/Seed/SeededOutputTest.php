<?php

declare(strict_types = 1);

namespace Quidam\Test\Seed;

use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Definitions\Addon\FillerInterface;
use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\BloodExtensionInterface;
use Quidam\Definitions\Extension\ColorExtensionInterface;
use Quidam\Definitions\Extension\CommerceExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Definitions\Extension\FileExtensionInterface;
use Quidam\Definitions\Extension\HashExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\LanguageExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Extension\SystemExtensionInterface;
use Quidam\Definitions\Extension\UserAgentExtensionInterface;
use Quidam\Definitions\Extension\UuidExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Definitions\Extension\VersionExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\Filler\FillerUser;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\PlProviderPack;
use Quidam\Test\Fixtures\Suit;
use Quidam\Testing\SeedSnapshot;

/**
 * Guards seed stability: the same seed, clock and calls give the same values. Every entry runs on a fresh instance,
 * so a changed value points to the method which changed.
 *
 * After an intended change regenerate the snapshot with "composer seed-snapshot".
 */
final class SeededOutputTest extends TestCase
{
    private const string SNAPSHOT = __DIR__ . '/snapshot.json';

    private const array SEEDS = [1, 42, 2026];

    private const int CALLS = 2;

    /** Methods which don't depend on randomness or are covered through other methods */
    private const array NOT_RANDOM = [
        PaymentExtensionInterface::class . '::currencyDecimals',
        PaymentExtensionInterface::class . '::localCurrency',
        PaymentExtensionInterface::class . '::formatPrice',
        PersonExtensionInterface::class . '::fullName',
        EdgeCasesInterface::class . '::categories',
        EdgeCasesInterface::class . '::strings',
        FillerInterface::class . '::withNullChance',
        TextInterface::class . '::withCorpus',
        TemplateInterface::class . '::aliases',
    ];

    public function testSeededOutputMatchesSnapshot(): void
    {
        $output = self::snapshot()->generate(self::calls(), self::scenarios());

        if (getenv('UPDATE_SEED_SNAPSHOT') === '1') {
            self::snapshot()->save($output);
            self::markTestSkipped('Snapshot updated');
        }

        self::assertGreaterThan(600, count($output));
        self::assertSame([], self::snapshot()->changed($output), 'Seeded output changed. If intended, run "composer seed-snapshot"');
    }

    public function testEveryPublicMethodIsInSnapshot(): void
    {
        $interfaces = array_values(array_unique(array_column(self::calls(), 0)));

        self::assertSame([], SeedSnapshot::uncovered($interfaces, self::calls(), self::NOT_RANDOM));
        self::assertCount(30, $interfaces, '24 extensions, the randomizer and 5 add-ons');
    }

    private static function snapshot(): SeedSnapshot
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-15 10:30:00.123456 UTC'));

        return new SeedSnapshot(self::SNAPSHOT, static fn (int $seed): Quidam => Quidam::create($seed, $clock), self::SEEDS, self::CALLS);
    }

    /**
     * Every public method of extensions and the randomizer, some with variants
     *
     * @return list<array{class-string, string, array<int|string, mixed>}>
     */
    private static function calls(): array
    {
        return [
            ...self::each(AddressExtensionInterface::class, [
                'city', 'streetName', 'streetSuffix', 'secondaryAddress', 'state', 'stateCode', 'stateData', 'buildingNumber',
                'streetAddress', 'postcode', 'address', 'country', 'countryCode', 'countryCodeAlpha3', 'countryData', 'latitude',
                'longitude', 'coordinates',
            ]),
            [AddressExtensionInterface::class, 'latitude', [49.0, 54.9]],
            ...self::each(AddressExtensionInterface::class, ['county', 'cityData', 'addressData']),
            [AddressExtensionInterface::class, 'addressData', ['cr']],
            ...self::each(AddressExtensionInterface::class, ['cityPrefix', 'citySuffix']),
            ...self::each(BarcodeExtensionInterface::class, ['ean13', 'ean8', 'upcA', 'isbn13', 'isbn10', 'ismn']),
            ...self::each(BloodExtensionInterface::class, ['bloodType', 'bloodRh', 'bloodGroup']),
            ...self::each(ColorExtensionInterface::class, [
                'hexColor', 'safeHexColor', 'rgbColorAsArray', 'rgbColor', 'rgbCssColor', 'rgbaCssColor', 'hslColorAsArray',
                'hslColor', 'hslCssColor', 'safeColorName', 'colorName',
            ]),
            ...self::each(CommerceExtensionInterface::class, ['productName', 'productAdjective', 'productMaterial', 'product', 'department', 'sku', 'promotionCode']),
            ...self::each(CompanyExtensionInterface::class, ['company', 'companySuffix', 'jobTitle', 'industry', 'catchPhrase']),
            ...self::each(DateTimeExtensionInterface::class, ['dateTime', 'dateTimeBetween', 'dateTimeRange', 'dateTimePast', 'dateTimeFuture', 'dateOfBirth', 'date', 'time', 'unixTime', 'timezone']),
            [DateTimeExtensionInterface::class, 'dateTimeRange', ['now', '+1 year', '14 days']],
            [DateTimeExtensionInterface::class, 'dateTimesBetween', [3, '-1 year']],
            [DateTimeExtensionInterface::class, 'timezone', ['US']],
            [DateTimeExtensionInterface::class, 'century', []],
            ...self::each(DateTimeExtensionInterface::class, ['monthName', 'dayOfWeek', 'localDate', 'localDateTime', 'longDate']),
            [EnumExtensionInterface::class, 'enumCase', [Suit::class]],
            [EnumExtensionInterface::class, 'enumValue', [Suit::class]],
            ...self::each(FileExtensionInterface::class, ['mimeType', 'extension', 'fileName', 'directoryPath', 'filePath', 'fileSize', 'fileSizeFormatted']),
            [FileExtensionInterface::class, 'mimeType', ['image']],
            [FileExtensionInterface::class, 'mimeTypeForExtension', ['pdf']],
            [FileExtensionInterface::class, 'filePath', ['pdf', 'windows']],
            [FileExtensionInterface::class, 'csv', [3]],
            [FileExtensionInterface::class, 'json', [2]],
            ...self::each(HashExtensionInterface::class, ['md5', 'sha1', 'sha256', 'sha512', 'base64', 'base64Url', 'jwt', 'apiKey']),
            [HashExtensionInterface::class, 'apiKey', ['sk_test_', 16]],
            ...self::each(InternetExtensionInterface::class, [
                'email', 'freeEmail', 'companyEmail', 'freeEmailDomain', 'userName', 'password', 'domainName', 'domainWord', 'tld',
                'url', 'protocol', 'hostname', 'hashtag', 'socialHandle', 'slug', 'ipv4', 'ipv4Cidr', 'localIpv4', 'ipv6',
                'ipv6Cidr', 'macAddress', 'port', 'httpMethod', 'httpStatusCode',
            ]),
            [InternetExtensionInterface::class, 'email', ['Anna', 'Kowalski']],
            [InternetExtensionInterface::class, 'url', [true]],
            ...self::each(InternetExtensionInterface::class, ['urlPath', 'safeEmailDomain']),
            ...self::each(LanguageExtensionInterface::class, ['language', 'languageCode', 'languageData', 'locale']),
            ...self::each(LoremExtensionInterface::class, ['word', 'words', 'sentence', 'sentences', 'paragraph', 'paragraphs', 'text']),
            ...self::each(NumberExtensionInterface::class, ['numberBetween', 'randomDigit', 'randomDigitNotZero', 'randomNumber', 'randomFloat', 'gaussian', 'gaussianInt', 'boolean']),
            [NumberExtensionInterface::class, 'numberBetween', [1, 10]],
            [NumberExtensionInterface::class, 'gaussian', [170.0, 10.0, 140.0, 210.0, 1]],
            [NumberExtensionInterface::class, 'percentage', []],
            ...self::each(PaymentExtensionInterface::class, [
                'creditCardType', 'creditCardNumber', 'maskedCreditCardNumber', 'creditCardExpirationDate', 'creditCardCvv',
                'creditCardDetails', 'iban', 'swiftBicNumber', 'currencyCode', 'currencyName', 'currencySymbol', 'currencyData',
                'price', 'priceInMinorUnits', 'bankAccountNumber', 'transactionType', 'bitcoinAddress',
                'ethereumAddress',
            ]),
            [PaymentExtensionInterface::class, 'iban', ['PL', '', true]],
            [PaymentExtensionInterface::class, 'priceInMinorUnits', [10.0, 50.0, 'JPY']],
            [PaymentExtensionInterface::class, 'ethereumAddress', [false]],
            [PaymentExtensionInterface::class, 'localPrice', []],
            ...self::each(PersonExtensionInterface::class, ['name', 'firstName', 'middleName', 'suffix', 'lastName', 'title', 'gender']),
            [PersonExtensionInterface::class, 'name', [Gender::Female]],
            ...self::each(PersonExtensionInterface::class, ['age', 'initials']),
            ...self::each(PhoneNumberExtensionInterface::class, ['phoneNumber', 'mobilePhoneNumber', 'internationalPhoneNumber', 'e164PhoneNumber', 'imei']),
            [PhoneNumberExtensionInterface::class, 'e164PhoneNumber', ['PL']],
            [ProfileExtensionInterface::class, 'person', []],
            [StringsExtensionInterface::class, 'string', []],
            [StringsExtensionInterface::class, 'numerify', ['%##-###']],
            [StringsExtensionInterface::class, 'lexify', ['??-??']],
            [StringsExtensionInterface::class, 'bothify', ['??-##-**']],
            [StringsExtensionInterface::class, 'regexify', ['(INV|FV)/20(2[4-6])/\d{5}[a-z]*']],
            [StringsExtensionInterface::class, 'hexadecimal', []],
            ...self::each(SystemExtensionInterface::class, ['cronExpression', 'logLevel', 'environmentVariable']),
            ...self::each(UserAgentExtensionInterface::class, ['userAgent', 'chrome', 'edge', 'opera', 'firefox', 'safari', 'botUserAgent']),
            [UserAgentExtensionInterface::class, 'userAgent', [true]],
            ...self::each(UuidExtensionInterface::class, ['uuid4', 'nanoid', 'snowflake', 'uuid7', 'ulid']),
            [UuidExtensionInterface::class, 'uuid3', [UuidExtensionInterface::NAMESPACE_DNS, 'example.com']],
            [UuidExtensionInterface::class, 'uuid5', [UuidExtensionInterface::NAMESPACE_URL, 'https://example.com/users/42']],
            ...self::each(VehicleExtensionInterface::class, ['vehicle', 'manufacturer', 'model', 'type', 'fuel', 'vin', 'licensePlate']),
            ...self::each(VersionExtensionInterface::class, ['semver', 'gitCommitHash', 'gitBranchName', 'gitCommitMessage', 'semverConstraint']),
            [VersionExtensionInterface::class, 'semver', [true, true]],
            ...self::each(RandomizerInterface::class, ['getBool', 'getBytes', 'randomLetter']),
            [RandomizerInterface::class, 'getInt', [1, 1000]],
            [RandomizerInterface::class, 'getFloat', [0.0, 1.0]],
            [RandomizerInterface::class, 'getBytesFromString', ['abc123', 10]],
            [RandomizerInterface::class, 'randomElement', [['a', 'b', 'c', 'd']]],
            [RandomizerInterface::class, 'randomElements', [range(1, 20), 5, true]],
            [RandomizerInterface::class, 'randomKey', [['x' => 1, 'y' => 2, 'z' => 3]]],
            [RandomizerInterface::class, 'weightedElement', [['new' => 70, 'paid' => 25, 'cancelled' => 5]]],
            [RandomizerInterface::class, 'shuffleElements', [range(1, 10)]],
            ...self::each(EdgeCasesInterface::class, ['string', 'longString']),
            [EdgeCasesInterface::class, 'string', ['injection']],
            [FillerInterface::class, 'make', [FillerUser::class]],
            [FillerInterface::class, 'array', [['id', 'firstName', 'email', 'city', 'postcode', 'phone', 'createdAt', 'isActive', 'price', 'whatever']]],
            ...self::each(RichTextInterface::class, ['html', 'htmlDocument', 'markdown']),
            [TextInterface::class, 'realText', []],
            [TemplateInterface::class, 'parse', ['{{ person.name }} <{{ internet.email }}> {{ number.numberBetween(1, 9) }}']],
            [TemplateInterface::class, 'evaluate', ['profile.person']],
        ];
    }

    /**
     * Generator level features
     *
     * @return array<string, Closure(Quidam): mixed>
     */
    private static function scenarios(): array
    {
        return [
            'optional' => static fn (Quidam $d): array => $d->many(10, static fn (Quidam $x): ?int => $x->optional(static fn (): int => 1)),
            'unique' => static fn (Quidam $d): array => $d->many(10, static fn (Quidam $x): int => $x->unique('digit', static fn (Quidam $y): int => $y->ext(NumberExtensionInterface::class)->randomDigit())),
            'valid' => static fn (Quidam $d): int => $d->valid(static fn (Quidam $x): int => $x->ext(NumberExtensionInterface::class)->randomDigit(), static fn (int $n): bool => $n > 7),
            'strategy' => static fn (Quidam $d): array => $d->many(5, static fn (Quidam $x): ?string => $x->strategy()->chance(50)->unique('emails')->generate(static fn (Quidam $y): string => $y->ext(InternetExtensionInterface::class)->email())),
            // language pack: locale conventions + data, consistent across extensions
            'locale' => static fn (Quidam $d): array => self::localeSample($d->withProvider(new PlProviderPack())),
            'fork' => static fn (Quidam $d): array => [$d->fork('users.1')->ext(PersonExtensionInterface::class)->name(), $d->fork('users.2')->fork('address')->ext(AddressExtensionInterface::class)->city()],
        ];
    }

    /** @return array<string, mixed> */
    private static function localeSample(Quidam $d): array
    {
        return [
            'name' => $d->ext(PersonExtensionInterface::class)->name(),
            'email' => $d->ext(InternetExtensionInterface::class)->email(),
            'phone' => $d->ext(PhoneNumberExtensionInterface::class)->internationalPhoneNumber(),
            'e164' => $d->ext(PhoneNumberExtensionInterface::class)->e164PhoneNumber(),
            'iban' => $d->ext(PaymentExtensionInterface::class)->iban(),
            'price' => $d->ext(PaymentExtensionInterface::class)->localPrice(),
            'date' => $d->ext(DateTimeExtensionInterface::class)->longDate(),
            'timezone' => $d->ext(DateTimeExtensionInterface::class)->timezone(),
        ];
    }

    /**
     * @param class-string $class
     * @param list<string> $methods
     * @return list<array{class-string, string, array<int|string, mixed>}>
     */
    private static function each(string $class, array $methods): array
    {
        return array_map(static fn (string $method): array => [$class, $method, []], $methods);
    }
}
