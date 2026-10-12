# Migration guide

From [FakerPHP](#from-fakerphp) or [DummyGenerator](#from-dummygenerator) to Quidam.

* [Notation](#notation)
* [From FakerPHP](#from-fakerphp)
  * [Setup, seed and locale](#setup-seed-and-locale)
  * [Calls](#calls)
  * [unique(), optional(), valid()](#unique-optional-valid)
  * [Method map](#method-map)
  * [Watch out - same name, different behaviour](#watch-out---same-name-different-behaviour)
  * [Own providers](#own-providers)
  * [A whole factory, before and after](#a-whole-factory-before-and-after)
* [From DummyGenerator](#from-dummygenerator)
* [Migrating seeded tests](#migrating-seeded-tests)

## Notation

In tables below `Person::name()` means `$generator->person()->name()` - every extension interface is `Quidam\Definitions\Extension\{Name}ExtensionInterface`. `random()->...` is `$generator->random()->...`.

Accessors are shortcuts of `$generator->ext(PersonExtensionInterface::class)`. In hot loops keep extensions in variables or properties - ~100 ns less per call:

```php
$person = $generator->person();
$internet = $generator->internet();

$person->name();
$internet->email();
```

## From FakerPHP

### Setup, seed and locale

```php
// Faker
$faker = Faker\Factory::create();
$faker = Faker\Factory::create('pl_PL');
$faker->seed(1234);

// Quidam
$generator = Quidam::create();
$generator = Quidam::create(seed: 1234);                        // seed is set once, at creation - immutable
$generator = Quidam::create(seed: 1234, clock: $frozenClock);   // also repeatable dates (Faker has no clock)
$generator = PlPlGenerator::create();                                // Polish - the one pack included
```

The core has **no locale**. Default data is English words with neutral, country-less formats (ISO dates, EUR, phone numbers without a country) - not Faker's US defaults. A language pack sets [`LocaleInterface`](locale.md) (conventions) and overrides extension data - see [Extending](extending.md#provider-pack---a-language-pack). One pack is included: [pl_PL](../src/LanguagePack/pl_PL/README.md) with Polish identifiers (`pesel`, `nip`, `regon`...); other locale-only Faker providers (`ssn`, `ein`, `vat`...) have no equivalent yet.

### Calls

Faker resolves methods and properties by name through magic `__get()` / `__call()`. Quidam fetches a typed extension - every call says which extension it uses, IDE autocompletion and PHPStan work:

```php
// Faker
$faker->name;
$faker->name();
$faker->email();
$faker->numberBetween(1, 10);
$faker->randomElement(['a', 'b']);

// Quidam
$generator->person()->name();
$generator->internet()->email();
$generator->number()->numberBetween(1, 10);
$generator->random()->randomElement(['a', 'b']);
```

`$faker->format('name')` and `$faker->parse('{{firstName}} {{lastName}}')` are not in the core - build strings in PHP:

```php
sprintf('%s <%s>', $person->name(), $internet->email());
```

For templates stored as data (YAML / JSON fixtures, database, CLI) use the opt-in [Template](addons/template.md) add-on - tokens name the extension: `{{ person.firstName }}` instead of `{{firstName}}`:

```php
$generator->ext(TemplateInterface::class)->parse('{{ person.firstName }} {{ person.lastName }}');
```

### unique(), optional(), valid()

In Faker modifiers wrap the generator and apply to the next call. In Quidam they wrap **one generation** - a closure receiving the generator:

| Faker | Quidam |
|---|---|
| `$faker->unique()->email()` | `$generator->unique('users.email', fn (Quidam $g) => $g->internet()->email())` |
| `$faker->unique(true)` (reset) | `$generator->resetUnique()` or `resetUnique('users.email')` |
| `$faker->unique(false, 500)->...` | `$generator->unique('key', fn ..., retries: 500)` |
| `$faker->optional()->sentence()` | `$generator->optional(fn (Quidam $g) => $g->lorem()->sentence())` |
| `$faker->optional(0.7)->sentence()` | `$generator->optional(fn ..., 0.7)` or `70` |
| `$faker->optional(0.7, 'n/a')->word()` | `$generator->optional(fn ..., 0.7) ?? 'n/a'` |
| `$faker->valid(fn ($n) => $n % 2 === 0)->randomDigit()` | `$generator->valid(fn (Quidam $g) => $g->number()->randomDigit(), fn (int $n) => $n % 2 === 0)` |
| `$faker->unique()->optional()->...` | `$generator->strategy()->chance(50)->unique('key')->generate(fn ...)` |
| many `unique()` fields in a factory | `withStrategy(UniqueStrategy::class)` once, then `generate('users.email', fn ...)` per field |

Faker scopes uniqueness by **method name** - `unique()->email()` and `unique()->safeEmail()` don't know about each other. Quidam scopes it by the **key** you give (i.e. a `table.column`), so different generators can share one scope. See [Strategies](strategies.md).

### Method map

Methods not listed have the same name in the extension you'd expect (`$faker->city()` → `Address::city()`).

**Base / numbers / strings**

| Faker | Quidam |
|---|---|
| `randomDigit()`, `randomDigitNotNull()` | `Number::randomDigit()`, `Number::randomDigitNotZero()` |
| `randomDigitNot(5)` | `valid(fn ($g) => $g->number()->randomDigit(), fn (int $n) => $n !== 5)` |
| `randomNumber()`, `numberBetween()`, `randomFloat()`, `boolean()` | `Number::...` (see [Watch out](#watch-out---same-name-different-behaviour)) |
| `randomElement()`, `randomElements()`, `randomKey()` | `random()->...` |
| `shuffle($array)`, `shuffleArray()` | `random()->shuffleElements($array)` |
| `shuffle($string)`, `shuffleString()` | `implode('', $generator->random()->shuffleElements(mb_str_split($string)))` |
| `randomLetter()` | `random()->randomLetter()` |
| `randomAscii()`, `asciify('***')` | `Strings::regexify('[!-~]')`, `Strings::regexify('[!-~]{3}')` |
| `numerify()`, `lexify()`, `bothify()` | `Strings::numerify()`, `lexify()`, `bothify()` |
| `regexify()` | `Strings::regexify()` - pattern without delimiters |
| `toLower()`, `toUpper()` | `mb_strtolower()`, `mb_strtoupper()` |
| `passthrough($value)` | `$value` |
| `biasedNumberBetween()` | `random()->weightedElement()` or `Number::gaussianInt()` |
| `md5()`, `sha1()`, `sha256()` | `Hash::...` |
| `uuid()` | `Uuid::uuid4()` |

**Person, company, phone**

| Faker | Quidam |
|---|---|
| `firstNameMale()`, `firstNameFemale()` | `Person::firstName(Gender::Male)` / `Gender::Female` |
| `titleMale()`, `titleFemale()` | `Person::title(Gender::Male)` / `Gender::Female` |
| `suffix()` | `Person::suffix()` |
| `catchPhrase()` | `Company::catchPhrase()` |
| `bs()` | - |
| `phoneNumber()` | `PhoneNumber::phoneNumber()` (landline), `mobilePhoneNumber()`, `internationalPhoneNumber()` |
| `e164PhoneNumber()`, `imei()` | `PhoneNumber::...` |
| `tollFreePhoneNumber()` | - (country specific, a language pack) |

**Address**

| Faker | Quidam |
|---|---|
| `state()`, `stateAbbr()` | `Address::state()`, `Address::stateCode()` - generic regions unless a pack sets real ones |
| `secondaryAddress()`, `streetSuffix()` | `Address::secondaryAddress()`, `streetSuffix()` |
| `cityPrefix()`, `citySuffix()` | `Address::cityPrefix()`, `citySuffix()` |
| `country()`, `countryCode()`, `countryISOAlpha3()` | `Address::country()`, `countryCode()`, `countryCodeAlpha3()` |
| `latitude()`, `longitude()` | `Address::latitude()`, `longitude()` |
| `localCoordinates()` | `Address::coordinates()` |

**Date and time** - Quidam returns `DateTimeImmutable` in the clock timezone, there's no `$timezone` parameter (use `SystemClock('Europe/Warsaw')` or `->setTimezone()`)

| Faker | Quidam |
|---|---|
| `dateTime()`, `dateTimeBetween()`, `date()`, `time()`, `unixTime()`, `timezone()` | `DateTime::...` |
| `dateTimeThisMonth()`, `dateTimeThisYear()`, `dateTimeThisDecade()`, `dateTimeThisCentury()` | `DateTime::dateTimeBetween('-1 month')`, `'-1 year'`, `'-10 years'`, `'-100 years'` |
| `dateTimeAD()` | `DateTime::dateTimeBetween('0001-01-01')` |
| `dateTimeInInterval('-1 year', '+5 days')` | `DateTime::dateTimeBetween('-1 year', '-1 year +5 days')` |
| `iso8601()` | `DateTime::dateTime()->format(DATE_ATOM)` |
| `amPm()`, `dayOfMonth()`, `dayOfWeek()`, `month()`, `monthName()`, `year()` | `DateTime::date('a')`, `date('d')`, `dayOfWeek()`, `date('m')`, `monthName()`, `date('Y')` |
| `century()` | `DateTime::century()` |
| `date('m/d/Y')` for "local" dates | `DateTime::localDate()` - format from the locale |

**Internet**

| Faker | Quidam |
|---|---|
| `safeEmail()` | `Internet::email()` - always in reserved `example.*` domains |
| `email()` | `Internet::email()` (safe) or `Internet::freeEmail()` (real providers) |
| `safeEmailDomain()` | `Internet::safeEmailDomain()` |
| `companyEmail()`, `freeEmail()`, `freeEmailDomain()`, `userName()`, `password()`, `domainName()`, `url()`, `slug()` | `Internet::...` |
| `ipv4()`, `ipv6()`, `localIpv4()`, `macAddress()` | `Internet::...` |
| `userAgent()`, `chrome()`, `firefox()`, `safari()`, `opera()` | `UserAgent::...` |
| `msedge()` | `UserAgent::edge()` |
| `internetExplorer()`, `windowsPlatformToken()`... | - |

**Payment, barcode, misc**

| Faker | Quidam |
|---|---|
| `creditCardType()`, `creditCardNumber()`, `creditCardDetails()`, `swiftBicNumber()` | `Payment::...` |
| `creditCardExpirationDate()` (`DateTime`) | `Payment::creditCardExpirationDate()` returns a string, format in 2nd parameter |
| `creditCardExpirationDateString($valid, 'm/y')` | `Payment::creditCardExpirationDate($valid, 'm/y')` |
| `iban($countryCode, $prefix, $length)` | `Payment::iban($countryCode, $prefix, $formatted)` - length follows the country |
| `currencyCode()` | `Payment::currencyCode()` |
| `ean13()`, `ean8()`, `isbn10()`, `isbn13()` | `Barcode::...` |
| `bloodType()`, `bloodRh()`, `bloodGroup()` | `Blood::...` |
| `hexColor()`, `rgbColor()`, `colorName()`... | `Color::...` |
| `languageCode()`, `locale()` | `Language::languageCode()`, `Language::locale()` |
| `semver()` | `Version::semver()` |
| `mimeType()`, `fileExtension()` | `File::mimeType()`, `File::extension()` |
| `file($sourceDir, $targetDir)` | - (copies real files); `File::filePath()` for a path, `File::csv()` / `json()` for contents |
| `randomHtml()` | `$generator->ext(RichTextInterface::class)->html()` ([Rich text](addons/rich-text.md) add-on), also `markdown()` |
| `imageUrl()`, `image()`, `emoji()` | - |

**Text**

| Faker | Quidam |
|---|---|
| `word()`, `sentence()`, `sentences()`, `paragraph()`, `paragraphs()`, `text()` | `Lorem::...` |
| `words(3, true)` | `implode(' ', $lorem->words(3))` |
| `sentences(3, true)`, `paragraphs(3, true)` | `implode(' ', ...)` / `implode("\n\n", ...)` |
| `realText(200)` | `$generator->ext(TextInterface::class)->realText(max: 200)` ([Text](addons/text.md) add-on) |
| `realTextBetween(160, 200)` | `$generator->ext(TextInterface::class)->realText(160, 200)` |

### Watch out - same name, different behaviour

| Method | Faker | Quidam |
|---|---|---|
| `randomElements($array, 3)` | 3 **different** elements (3rd parameter is `$allowDuplicates = false`) | elements **can repeat** (3rd parameter is `$unique = false`) - use `randomElements($array, 3, unique: true)` |
| `optional(1)` | `1` = 100% (weight 0-1, values > 1 divided by 100) | `1` (int) = **1%** - int is a percent, float a probability: use `1.0` or `100` |
| `numberBetween(10, 1)` | swaps the limits | throws `InvalidArgumentException` |
| `randomFloat()` | no decimals limit, max = random | 2 decimals, 0-1000; never rounded out of range |
| `email()` | mix of free and safe domains | always reserved `example.com/org/net` |
| `password()` | min length 6, may lack character types | min length 8, always lower + upper + digit + special |
| `phoneNumber()` | US-style `en_US` formats | neutral national format without a country, or the language pack's |
| `address()` | `"587 Whitaker Avenue\nPriceport, CA 81319"` (US) | `'Hunter Square 42, 53154 West Raymond'` (neutral) |
| `dateTime*()` | mutable `DateTime`, `$timezone` parameter | `DateTimeImmutable` in clock timezone |
| `creditCardExpirationDate()` | `DateTime` | `string` (`'m/y'`) |
| `seed()` | changes the shared generator at any time | seed given to `create()`; `with*()` starts the sequence again |
| `unique()` | per method name, until `unique(true)` | per key, per generator - `with*()` starts empty |

### Own providers

```php
// Faker
class CarProvider extends \Faker\Provider\Base
{
    public function carBrand(): string
    {
        return static::randomElement(['Toyota', 'Ford']);
    }
}
$faker->addProvider(new CarProvider($faker));
$faker->carBrand();

// Quidam - any class, no registration, dependencies autowired
class Car
{
    public function __construct(private readonly RandomizerInterface $randomizer) {}

    public function brand(): string
    {
        return $this->randomizer->randomElement(['Toyota', 'Ford']);
    }
}
$generator->ext(Car::class)->brand();
```

Replacing a built-in provider (`$faker->addProvider(new MyPerson($faker))`) is `$generator->withDefinition(PersonExtensionInterface::class, MyPerson::class)`, where `MyPerson extends Person` - and unlike in Faker, extensions using names (`Internet`, `Company`, `Address`) use it too. See [Extending](extending.md).

### A whole factory, before and after

```php
// Faker
$faker = Faker\Factory::create();
$faker->seed(2026);

$user = [
    'name' => $faker->name(),
    'email' => $faker->unique()->safeEmail(),
    'phone' => $faker->optional(0.7)->phoneNumber(),
    'birthDate' => $faker->dateTimeBetween('-80 years', '-18 years'),
    'createdAt' => $faker->dateTimeThisYear(),
];

// Quidam
$generator = Quidam::create(seed: 2026, clock: new FrozenClock('2026-10-08 12:00 UTC'));
$person = $generator->profile()->person();   // name, email and age belong together

$user = [
    'name' => $person['name'],
    // unique email of this person - random digits in some formats make retries useful
    'email' => $generator->unique(
        'users.email',
        fn (Quidam $g) => $g->internet()->email($person['firstName'], $person['lastName']),
    ),
    'phone' => $generator->optional(fn (Quidam $g) => $g->phoneNumber()->phoneNumber(), 70),
    'birthDate' => $person['dateOfBirth'],
    'createdAt' => $generator->dateTime()->dateTimeBetween('-1 year'),
];
```

A closure returning a fixed value (`fn () => $person['email']`) can't help `unique()` - on a collision it would get the same value on every retry and throw `OverflowException`.

## From DummyGenerator

Quidam keeps DummyGenerator's architecture - a container with interface ids, `DefinitionPack`, `ProviderPack`, `withDefinition()`, `ext()`, calculators as services, `StrategyInterface` - and removes the magic.

### Setup, seed and clock

```php
// DummyGenerator
$generator = DummyGenerator::create();
$generator = $generator->withDefinition(RandomizerInterface::class, new XoshiroRandomizer(seed: 123));
$generator = $generator->withDefinition(SystemClockInterface::class, new FrozenClock(new DateTimeImmutable('2025-08-11')));
$generator = $generator->withProvider(new PlProviderPack());

// Quidam
$generator = Quidam::create(seed: 123, clock: new FrozenClock('2025-08-11'));   // any PSR-20 clock
$generator = $generator->withProvider(new PlProviderPack());                         // same mechanism
```

* default randomizer: DummyGenerator used `Random\Engine\Secure`, Quidam uses `XoshiroRandomizer` (fast, seeded or random seed) - `createSecure()` / `secure()` for the secure one, which points to `SecureRandomizerInterface`
* `SystemClockInterface` → PSR-20 `Psr\Clock\ClockInterface`; `SystemClock` and `FrozenClock` included
* `DiContainerFactory::all()` / `base()` / `default()` groups → one `DefinitionPack` (24 extensions); PHP-DI → built-in container, PHP-DI through an [adapter](containers.md)
* `dummyproviders` packages don't work with Quidam - extension interfaces differ (methods changed, below)

### Calls and strategies

```php
// DummyGenerator - magic call, extension found by method name
$generator->firstName();
$generator->parse('{{ firstName }} {{ numberBetween(1, 10) }}');

// Quidam - typed extension; templates as an opt-in add-on with extension names in tokens
$generator->person()->firstName();
$generator->ext(TemplateInterface::class)->parse('{{ person.firstName }} {{ number.numberBetween(1, 10) }}');
```

Strategies keep the interface (`generate(string $name, callable $callback)`), so own strategies work unchanged. What changed: a generator-wide strategy applies to `generate($name, fn)`, not to every call, and the key is explicit:

```php
// DummyGenerator
$container = DiContainerFactory::all();
$container->set(StrategyInterface::class, new CompositeStrategy([new UniqueStrategy(1000), new ChanceStrategy(0.5)]));
$generator = new DummyGenerator($container);
$generator->email();

// Quidam - generator-wide, the same idea (a factory: the generator's randomizer and unique memory)
$generator = Quidam::create()->withDefinition(StrategyInterface::class, fn (RandomizerInterface $random, UniqueMemory $memory) => new CompositeStrategy([
    new ChanceStrategy(0.5, $random),
    new UniqueStrategy($memory, retries: 1000),
]));
$generator->generate('email', fn (Quidam $g) => $g->internet()->email());

// or per value
$generator->strategy()->chance(0.5)->unique('users.email', 1000)->generate(fn (Quidam $g) => ...);
```

Note the order: in DummyGenerator's `CompositeStrategy` later strategies wrap earlier ones (`[Unique, Chance]` - chance is outermost), in Quidam the **first is the outermost** - so the list is reversed. Constructors changed: `UniqueStrategy(?UniqueMemory $memory, ?string $key, int $retries)`, `ChanceStrategy(int|float $chance, ?RandomizerInterface $randomizer, mixed $default)`. See [Strategies](strategies.md#generator-wide-strategy).

### Extensions

Interfaces keep their names (`PersonExtensionInterface`...), but methods were redesigned - typed, fewer and more consistent. Changed and moved methods:

| DummyGenerator | Quidam |
|---|---|
| `numerify()`, `lexify()`, `bothify()` (Replacer) | `Strings::...` |
| `toLower()`, `toUpper()`, `strlen()` (Replacer) | `mb_strtolower()`, `mb_strtoupper()`, `mb_strlen()` |
| `transliterate()` (Replacer) | `$generator->ext(TransliteratorInterface::class)->transliterate()` |
| `shuffleString()` (Replacer) | `implode('', $generator->random()->shuffleElements(mb_str_split($string)))` |
| `firstNameMale()`, `titleFemale()`... | `Person::firstName(Gender::Male)`, `Person::title(Gender::Female)` |
| `gender` as `'male'` / `'female'` string | `Gender` enum |
| `countryISOAlpha2()`, `countryISOAlpha3()` (Country) | `Address::countryCode()`, `countryCodeAlpha3()` |
| `latitude()`, `longitude()`, `coordinates()` (Coordinates) | `Address::...` |
| `anyDate()`, `anyDateBetween()`, `anyTimezone()` (AnyDateTime) | `DateTime::dateTimeBetween()`, `DateTime::timezone()` |
| `dateTime*(..., $timezone)` | no `$timezone` - clock timezone or `->setTimezone()` |
| `dateTimeThisWeek()` ... `dateTimeThisCentury()`, `dateTimeAD()` | `DateTime::dateTimeBetween('-1 week')` ... `('-100 years')`, `('0001-01-01')` |
| `cronExpression()` (DateTime) | `System::cronExpression()` |
| `safeEmail()` | `Internet::email()` - `email()` is safe now |
| `publicIpv4()` | `Internet::ipv4()` - public by default |
| `jwt()` (Internet) | `Hash::jwt()` |
| `randomDigitNot(5)` | `valid(fn ($g) => ..., fn (int $n) => $n !== 5)` |
| `hexadecimal(6)`, `binary(8)` | `Strings::hexadecimal(6)`, `Strings::regexify('[01]{8}')` |
| `fileSize($min, $max, formatted: true)` | `File::fileSizeFormatted($min, $max)` |
| `internetExplorer()`, `*PlatformToken()` | - |
| `nilUuid()` | `UuidExtensionInterface::NIL` constant |
| `biasedNumberBetween()` (Biased) | `random()->weightedElement()`, `Number::gaussianInt()` |

Find other methods in the [index](index.md#extensions).

### Replacing implementations

| DummyGenerator | Quidam |
|---|---|
| `withDefinition(PersonExtensionInterface::class, MyPerson::class)` | the same - `MyPerson extends Person` (override data only) or implements the interface |
| `withDefinition(LuhnCalculatorInterface::class, ...)` and other calculators | the same - `checkDigit()` / `isValid()` methods |
| `withDefinition(RandomizerInterface::class, ...)` | the same; also `SecureRandomizerInterface` |
| `withDefinition(ReplacerInterface::class, ...)` | `withDefinition(StringsExtensionInterface::class, MyStrings::class)` |
| own extension: `implements DefinitionInterface`, registered | any class with `ext(MyExtension::class)` - autowired, no registration; or own interface registered with `withDefinition()` |
| `ext($id)` | `ext($id)` - returns the typed object |

## Migrating seeded tests

Seeded values differ between Faker, DummyGenerator and Quidam - the same seed gives different data, and fixtures or snapshots with hardcoded seeded values have to be regenerated once. When you do, consider [forks](quidam.md#forks---stable-records): `$generator->fork('users.' . $id)` keeps each record stable when other records or fields change, so the next regeneration isn't needed for unrelated changes.
