# Extending

Every extension is a definition under its interface. To change one, register another class under the same interface - the container then uses it everywhere, also as a dependency of other extensions (your `Person` is used by `Internet`, `Address`, `Company` and `Profile` too).

* [Two ways to replace an extension](#two-ways-to-replace-an-extension)
* [Override data - language packs](#override-data---language-packs)
* [Override behaviour](#override-behaviour)
* [Provider pack - a language pack](#provider-pack---a-language-pack)
* [Definition pack](#definition-pack)
* [Calculators](#calculators)
* [Own extensions](#own-extensions)
* [Overridable constants](#overridable-constants)
* [Static analysis](#static-analysis)
* [What can't be changed](#what-cant-be-changed)

## Two ways to replace an extension

| | Extend the default | Implement the interface |
|---|---|---|
| | `class PlPerson extends Person` | `class PlPerson implements PersonExtensionInterface` |
| what you write | only what differs - usually data constants | every method of the interface |
| dependencies | inherited constructor - autowired | your own constructor - autowired |
| when | language packs, small changes | a different source of data (database, API, files), a completely different implementation |

Both are registered the same way:

```php
$generator = $generator->withDefinition(PersonExtensionInterface::class, PlPerson::class);
```

## Override data - language packs

Data lists are `protected const` and extensions read them through `static::`, so a subclass can replace only the data - all methods keep working:

```php
use Quidam\Core\Person;
use Quidam\Definitions\Enum\Gender;

class PlPerson extends Person
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = ['Jan', 'Piotr', 'Krzysztof', 'Andrzej', 'Tomasz', 'Paweł'];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = ['Anna', 'Maria', 'Katarzyna', 'Małgorzata', 'Agnieszka'];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];       // English unisex names (Alex, Taylor) don't fit

    /** @var list<string> */
    protected const array LAST_NAME = ['Nowak', 'Kowalski', 'Wiśniewski', 'Wójcik', 'Kowalczyk'];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Pan'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Pani'];
}

$pl = $generator->withDefinition(PersonExtensionInterface::class, PlPerson::class);

$pl->person()->name();               // 'Małgorzata Wójcik'
$pl->person()->title(Gender::Male);  // 'Pan'
$pl->company()->company();           // 'Nowak & Kowalczyk' - Company uses Person
$pl->internet()->email();            // 'malgorzata.wojcik@example.org' - transliterated
```

Constants you don't override keep the original data. Formats are constants too, so most language packs need no code - Polish addresses have the street before the number (the default already) and postcodes like `61-482`:

```php
use Quidam\Core\Address;

class PlAddress extends Address
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Nowy', 'Stary', 'Górny', 'Dolny'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['ów', 'owo', 'ice', 'in'];

    /** @var list<string> */
    protected const array POSTCODE = ['##-###'];

    /** @var array<string, string> */
    protected const array STATES = ['02' => 'dolnośląskie', '04' => 'kujawsko-pomorskie', '06' => 'lubelskie' /* ... */];

    protected const string COUNTY_FORMAT = 'powiat {name}';
}
```

## Override behaviour

Public methods and protected helpers can be overridden too - when the format differs beyond what the constants allow. Extensions keep their dependencies in `protected readonly` properties (`$this->randomizer`, `$this->person`, `$this->strings`, `$this->locale`...), so overriding methods can use them:

```php
class PlAddress extends Address
{
    // ... constants as above

    public function streetName(): string
    {
        return 'ul. ' . $this->randomizer->randomElement(['Polna', 'Leśna', 'Słoneczna', 'Krótka', 'Szkolna']);
    }
}

$pl->withDefinition(AddressExtensionInterface::class, PlAddress::class)
    ->address()->address();     // 'ul. Leśna 27, 61-482 Nowakowo'
```

`address()` and `addressData()` both use `streetLine()` and `formatAddress()` - override those to add flats or change the layout in one place.

## Provider pack - a language pack

To use a language pack in many places, put its definitions in a `ProviderPackInterface` - it's DummyGenerator's mechanism. A complete pack is included: [pl_PL](../src/LanguagePack/pl_PL/README.md) - locale, data, own methods, own extension, a typed generator subclass and tests with a seed snapshot, all in one folder. A minimal one:

```php
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

final readonly class PlProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            // country conventions - one definition, used by Payment, PhoneNumber, DateTime...
            LocaleInterface::class => new Locale(
                countryCode: 'PL', languageCode: 'pl', callingCode: '48', trunkPrefix: '',
                currencyCode: 'PLN', currencySymbol: 'zł', decimalSeparator: ',', thousandsSeparator: "\u{00A0}",
                pricePattern: "{amount}\u{00A0}{symbol}", dateFormat: 'd.m.Y',
            ),
            // language data
            PersonExtensionInterface::class => PlPerson::class,
            AddressExtensionInterface::class => PlAddress::class,
            DateTimeExtensionInterface::class => PlDateTime::class,       // MONTHS, MONTHS_IN_DATES, DAYS
            PhoneNumberExtensionInterface::class => PlPhoneNumber::class, // FORMATS, MOBILE_FORMATS
        ];
    }
}

$pl = Quidam::create(seed: 1)->withProvider(new PlProviderPack());

$pl->phoneNumber()->internationalPhoneNumber();   // '+48 512 345 678'
$pl->payment()->localPrice();                     // '693,34 zł'
$pl->dateTime()->longDate();                      // '9 sierpnia 2014'
$pl->payment()->iban();                           // 'PL49 ...'
```

The test suite has this minimal pack (`test/Fixtures/PlProviderPack.php`) and checks that all conventions stay consistent.

A pack can also wrap the generator - a `readonly` subclass whose `create()` applies the pack, with accessors typed with the pack classes, so their own methods are visible to IDEs and PHPStan (see [`PlPlGenerator`](../src/LanguagePack/pl_PL/PlPlGenerator.php)):

```php
readonly class PlPlGenerator extends Quidam
{
    public static function create(?int $seed = null, ?ClockInterface $clock = null, /* ... */): static
    {
        return parent::create($seed, $clock /* ... */)->withProvider(new PlPlProviderPack());
    }

    public function phoneNumber(): PhoneNumber   // pack class: tollFreePhoneNumber() is typed
    {
        // ext() of the interface + instanceof check - replacements registered under the interface still apply
    }
}
```

## Definition pack

`DefinitionPackInterface` holds **all** default definitions - `coreDefinitions()` (seed, randomizers, strategy, clock, locale, transliterator), `calculators()`, `extensions()`, `addons()` (lazy, opt-in) and `all()`. Pass your own to `create()` to change defaults before the first container is built - useful when a pack replaces something `secure()` resolves later, or to drop extensions you don't use:

```php
use Quidam\DefinitionPack\DefinitionPack;

final class PlDefinitionPack extends DefinitionPack
{
    public function coreDefinitions(): array
    {
        return [...parent::coreDefinitions(), SecureRandomizerInterface::class => HsmRandomizer::class];
    }

    public function extensions(): array
    {
        return [...parent::extensions(), PersonExtensionInterface::class => PlPerson::class];
    }
}

$generator = Quidam::createSecure(definitionPack: new PlDefinitionPack());
```

## Calculators

Check digits are services with interfaces - replace one to change it everywhere, i.e. to get **invalid** numbers for negative tests:

| Interface | Default | Used by |
|---|---|---|
| `LuhnCalculatorInterface` | `LuhnCalculator` | `Payment` card numbers, `PhoneNumber::imei()` |
| `EanCalculatorInterface` | `EanCalculator` | `Barcode` EAN-13, EAN-8, UPC-A, ISBN-13, ISMN |
| `IsbnCalculatorInterface` | `IsbnCalculator` | `Barcode::isbn10()` |
| `IbanCalculatorInterface` | `IbanCalculator` | `Payment::iban()` |

Each has `checkDigit()` / `checksum()` and `isValid()` - usable in your code to validate values too:

```php
use Quidam\Core\Calculator\LuhnCalculator;
use Quidam\Definitions\Calculator\LuhnCalculatorInterface;

final class InvalidLuhnCalculator extends LuhnCalculator
{
    public function checkDigit(string $number): string
    {
        return (string) (((int) parent::checkDigit($number) + 1) % 10);   // always wrong
    }
}

$invalid = $generator->withDefinition(LuhnCalculatorInterface::class, InvalidLuhnCalculator::class);
$invalid->payment()->creditCardNumber();   // fails Luhn validation
$invalid->phoneNumber()->imei();          // fails too - same service

$generator->ext(LuhnCalculatorInterface::class)->isValid('4539148803436467');   // true
```

Encodings used by a single extension stay protected methods: `Vehicle::vinCheckDigit()`, `Payment::base58Check()`, `bech32()`, `eip55Checksum()`, `keccak256()` - override them in a subclass.

## Own extensions

Any class works with `$generator->ext(MyExtension::class)` - see [Own extensions](quidam.md#own-extensions). To make yours replaceable like the built-in ones, follow the same pattern:

```php
interface CarExtensionInterface extends \Quidam\Definitions\Extension\ExtensionInterface
{
    public function brand(): string;
}

class Car implements CarExtensionInterface
{
    /** @var list<string> */
    protected const array BRANDS = ['Toyota', 'Ford', 'Fiat'];

    public function __construct(protected readonly RandomizerInterface $randomizer)
    {
    }

    public function brand(): string
    {
        return $this->randomizer->randomElement(static::BRANDS);
    }
}

$generator = Quidam::create()->withDefinition(CarExtensionInterface::class, Car::class);
$generator->ext(CarExtensionInterface::class)->brand();
```

Data as `protected const` read through `static::`, dependencies through interfaces - then a language pack can override your data, and others can replace the randomizer, the locale or your extension.

Extending `ExtensionInterface` is optional: it's an empty marker interface, and Quidam never checks for it. It only labels the interface as an extension for readers and tools. `ext()` and `withDefinition()` work the same without it.

## Overridable constants

| Extension | Constants |
|---|---|
| `Address` | `CITY_PREFIX`, `CITY_SUFFIX`, `STREET_SUFFIX`, `BUILDING_NUMBER`, `POSTCODE`, `SECONDARY_ADDRESS`, `STATES`, `COUNTY_FORMAT`, `STREET_ADDRESS_FORMAT`, `ADDRESS_FORMAT`, `COUNTRIES` |
| `Barcode` | `GS1_PREFIXES`, `ISBN_978_GROUPS`, `ISBN_979_GROUPS`, `UPC_NUMBER_SYSTEMS` |
| `Blood` | `GROUPS` |
| `Color` | `SAFE_COLOR_NAMES`, `COLOR_NAMES` |
| `Commerce` | `PRODUCT_ADJECTIVE`, `PRODUCT_MATERIAL`, `PRODUCT`, `DEPARTMENT`, `SKU_FORMATS`, `PROMOTION_WORD`, `PROMOTION_DISCOUNT` |
| `Company` | `SUFFIX`, `COMPANY_FORMATS`, `NAME_WORD`, `JOB_TITLE`, `INDUSTRY`, `CATCH_PHRASE_WORDS` |
| `DateTime` | `MONTHS`, `MONTHS_IN_DATES`, `DAYS` (formats are in [`LocaleInterface`](locale.md)) |
| `File` | `MIME_TYPES`, `BASE_NAMES`, `NAME_SUFFIX`, `PHOTO_EXTENSIONS`, `DIRECTORIES`, `SUBDIRECTORIES`, `USER_NAMES` (and `record()` method for CSV / JSON columns) |
| `Internet` | `FREE_EMAIL_DOMAIN`, `SAFE_EMAIL_DOMAIN`, `TLD`, `DOMAIN_WORD`, `PASSWORD_LOWER`, `PASSWORD_UPPER`, `PASSWORD_DIGIT`, `PASSWORD_SPECIAL`, `LOCAL_IP_BLOCKS`, `HOST_PREFIXES`, `HTTP_METHODS`, `HTTP_STATUS_CODES` |
| `Language` | `LANGUAGES`, `LOCALES` |
| `Lorem` | `WORDS` |
| `Payment` | `CARDS`, `IBAN_FORMATS`, `CURRENCIES`, `CURRENCY_SYMBOLS`, `CURRENCY_DECIMALS`, `TRANSACTION_TYPES` (local currency and price format are in `LocaleInterface`) |
| `Person` | `FIRST_NAME_MALE`, `FIRST_NAME_FEMALE`, `FIRST_NAME_NEUTRAL` (unisex, given to both genders), `LAST_NAME`, `TITLE_MALE`, `TITLE_FEMALE`, `SUFFIX`, `NAME_FORMAT` |
| `PhoneNumber` | `FORMATS`, `MOBILE_FORMATS`, `E164_FORMATS`, `IMEI_REPORTING_BODIES` (calling code is in `LocaleInterface`) |
| `System` | `LOG_LEVELS`, `ENV_PREFIX`, `ENV_SUFFIX` |
| `UserAgent` | `BROWSERS`, `MOBILE_CHANCE`, `DESKTOP_PLATFORMS`, `FIREFOX_DESKTOP_PLATFORMS`, `ANDROID_PLATFORM`, `CHROME_RELEASE`, `FIREFOX_RELEASE`, `OPERA_OFFSET`, `OLDER_VERSIONS`, `BOTS` |
| `Vehicle` | `MANUFACTURERS`, `TYPES`, `FUELS`, `LICENSE_PLATE_FORMATS` |
| `Version` | `PRE_RELEASE`, `CHANGE_TYPES`, `CHANGE_VERBS`, `CHANGE_SUBJECTS`, `CHANGE_SCOPES`, `MAIN_BRANCHES` |
| `Transliterator` | `TABLE` |

Examples beyond language packs:

```php
use Quidam\Core\Barcode;
use Quidam\Core\UserAgent;

// only Polish products and books
class PolishBarcode extends Barcode
{
    /** @var list<array{int, int}> */
    protected const array GS1_PREFIXES = [[590, 590]];

    /** @var list<string> */
    protected const array ISBN_978_GROUPS = ['83'];
}

// browser release cadence anchors updated without waiting for a new library version
class UpdatedUserAgent extends UserAgent
{
    /** @var array{int, string} */
    protected const array CHROME_RELEASE = [150, '2026-06-09'];
}
```

## Static analysis

Every overridable array constant has a `@var` type (i.e. `list<string>`). Add the same `@var` in your subclass - PHPStan checks that the type is compatible:

```
Type list<int> of constant BadPerson::LAST_NAME is not covariant with type list<string> of constant
Quidam\Core\Person::LAST_NAME.
```

Accessors and `ext()` return the interface type - methods your subclass adds are visible after `instanceof` or through your own interface.

## What can't be changed

* constants which define a format or algorithm stay `private` - changing them would only produce invalid output: `Uuid` Crockford alphabet and timestamp limit, `Number` float precision limit, `Payment` BIC, Base58 and Bech32 alphabets and Keccak constants, `Vehicle` VIN alphabet, year codes and check digit weights, `File` size units, `Hash` Base62 alphabet, `DateTime` parsed date cache size
* a replacement registered under an interface has to implement it - `ext()` checks `instanceof` and throws `ContainerException` otherwise
* `Gender` is an enum - public API, not data
