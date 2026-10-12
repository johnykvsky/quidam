# Quidam

`Quidam\Quidam` is the entry point. It's a thin, immutable facade over a container: every extension is a service registered under its **interface**, created on first use and shared within the generator. Extensions depend on each other through these interfaces, so replacing one changes it everywhere.

* [Creating a generator](#creating-a-generator)
* [Extensions - accessors and `ext()`](#extensions---accessors-and-ext)
* [Optional, unique and valid values](#optional-unique-and-valid-values)
* [Many values and sequences](#many-values-and-sequences)
* [Own extensions](#own-extensions)
* [Replacing definitions - `withDefinition()`](#replacing-definitions---withdefinition)
* [Own accessors - extending Quidam](#own-accessors---extending-quidam)
* [Randomizers](#randomizers)
* [Clock](#clock)
* [Locale](#locale)
* [Seed and repeatable data](#seed-and-repeatable-data)
* [Forks - stable records](#forks---stable-records)
* [Container](#container)
* [What can be replaced](#what-can-be-replaced)
* [Exceptions](#exceptions)
* [Architecture](#architecture)

## Creating a generator

```php
use Quidam\Clock\FrozenClock;
use Quidam\Clock\SystemClock;
use Quidam\Quidam;

$generator = Quidam::create();                                         // random data
$generator = Quidam::create(seed: 123);                                // same data on every run
$generator = Quidam::create(clock: new SystemClock('Europe/Warsaw'));  // dates in given timezone
$generator = Quidam::create(seed: 123, clock: new FrozenClock('2026-10-08 12:00 UTC')); // also repeatable dates
$generator = Quidam::createSecure();                                   // cryptographically secure randomness
```

All parameters of `create()`:

| Parameter | Default | |
|---|---|---|
| `?int $seed` | `null` - random | seed of the default randomizer |
| `?ClockInterface $clock` | `SystemClock` | PSR-20 clock - "now" of dates, `uuid7()`, card expiry... |
| `?DefinitionPackInterface $definitionPack` | `DefinitionPack` | all default definitions - see [Extending](extending.md#definition-pack) |
| `?ContainerFactoryInterface $containerFactory` | `SimpleContainerFactory` | container implementation - see [Containers](containers.md) |

## Extensions - accessors and `ext()`

Every default extension has a typed accessor - `$generator->person()` is a shortcut of `$generator->ext(PersonExtensionInterface::class)`:

```php
$generator->person()->name();               // 'Brenda Patterson'
$generator->address()->city();              // 'Lilyview'
$generator->number()->numberBetween(1, 10); // 4
$generator->strings()->bothify('??-##');    // 'hc-38'

$person = $generator->person();             // or keep the extension
$person->firstName();
```

Accessors return the interface type, so IDEs and PHPStan know it - every method is a real method, with autocompletion and static analysis. They come from the `Quidam\ExtensionAccessorsTrait` trait (not part of `GeneratorInterface`, which stays minimal
- type against `Quidam` to use them) and resolve interfaces from the container, so replacements (`withDefinition()`, language packs) apply to them too. The same object is returned on every call within a generator; fetching it costs ~100 ns (an accessor ~40 ns more than `ext()`), so keeping it in a variable is a matter of taste, not speed.

All 24 extensions are listed in the [index](index.md#extensions) - accessor names are their names in camelCase (`dateTime()`, `phoneNumber()`, `userAgent()`). The randomizer has a shortcut too: `$generator->random()`.

Anything else - add-ons, your own extensions, calculators - is fetched with `ext()`, by its container id:

```php
$generator->ext(TemplateInterface::class)->parse('{{ person.name }}');
$generator->ext(Car::class)->brand();
```

`ext()` is generic (`@template T`, `class-string<T>` → `T`), so the result is typed as well. Use interfaces, not classes: the interface is the container id, and replacements are registered under it. `ext(Person::class)` autowires the default class - it works, but bypasses replacements.

## Optional, unique and valid values

```php
// nullable fields - value with given chance, null otherwise; int is a percent, float a probability
$generator->optional(fn (Quidam $g) => $g->lorem()->sentence(), 30);

// unique per key
$email = $generator->unique('users.email', fn (Quidam $g) => $g->internet()->email());

// valid - retries until the validator accepts
$even = $generator->valid(
    fn (Quidam $g) => $g->number()->randomDigit(),
    fn (int $n) => $n % 2 === 0,
);

// mixed - first added is the outermost one
$phone = $generator->strategy()
    ->chance(70)
    ->unique('contacts.phone')
    ->generate(fn (Quidam $g) => $g->phoneNumber()->e164PhoneNumber());
```

Plus generator-wide strategies (`withStrategy()` + `generate()`) and own strategies - see [Strategies](strategies.md).

## Many values and sequences

`many()` returns a list - the callback receives the generator and the index (0-based):

```php
$users = $generator->many(10, fn (Quidam $g, int $i) => [
    'id' => $i + 1,
    'name' => $g->person()->name(),
    'email' => $g->unique('users.email', fn (Quidam $g) => $g->internet()->email()),
]);

$tags = $generator->many(3, fn (Quidam $g) => $g->lorem()->word());   // ['iusto', 'similique', 'atque']
```

`sequence()` is a counter per key - for values which must be unique and readable (logins, invoice numbers, SKUs):

```php
$generator->sequence('users');                                  // 1
$generator->sequence('users');                                  // 2
$generator->sequence('invoices', 1000);                         // 1000 - $start used on first call only
sprintf('user%d@example.com', $generator->sequence('users'));   // 'user3@example.com'
sprintf('INV/2026/%05d', $generator->sequence('invoices'));     // 'INV/2026/01001'

$generator->resetSequence('users');                             // next 'users' is 1 again
$generator->resetSequence();                                    // all counters
```

Sequences don't use the randomizer - they're the same with and without seed. Like unique memory, counters belong to the generator: `with*()` starts them again, [forks](#forks---stable-records) share them.

## Own extensions

Any class can be an extension - no registration, no interface. Constructor dependencies are autowired by type, so an extension can use the randomizer, clock, locale and any built-in extension:

```php
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

class Car
{
    private const array BRANDS = ['Toyota', 'Ford', 'Fiat', 'Skoda', 'Volvo'];

    public function __construct(
        private readonly RandomizerInterface $randomizer,
        private readonly PersonExtensionInterface $person,    // depend on interfaces - replacements apply
    ) {
    }

    public function brand(): string
    {
        return $this->randomizer->randomElement(self::BRANDS);
    }

    public function owner(): string
    {
        return $this->person->name();
    }
}

$car = $generator->ext(Car::class);     // typed as Car
$car->brand();                          // 'Ford'
$car->owner();
```

When a constructor needs values that can't be autowired (scalars without default, unbound interfaces), register a factory - its parameters are resolved by type too:

```php
$generator = $generator->withDefinition(
    Car::class,
    fn (RandomizerInterface $randomizer, PersonExtensionInterface $person) => new Car($randomizer, $person),
);
```

To make your extension replaceable like the built-in ones, give it an interface and register it: `withDefinition(CarExtensionInterface::class, Car::class)` - then others can replace `CarExtensionInterface`.

## Replacing definitions - `withDefinition()`

`withDefinition(string $id, mixed $definition)` returns a **new** generator; the original one is not changed:

| Definition | Example | Meaning |
|---|---|---|
| class name | `->withDefinition(PersonExtensionInterface::class, MyPerson::class)` | autowired instance of given class (alias) |
| object | `->withDefinition(RandomizerInterface::class, new XoshiroRandomizer(5))` | used as is |
| closure | `->withDefinition(Car::class, fn (RandomizerInterface $r) => new Car(...))` | factory, parameters by type, called once per generator |
| other value | `->withDefinition('app.prefix', 'TEST-')` | returned as is |

`withDefinitions([...])` sets many at once, `withProvider(ProviderPackInterface $pack)` sets the definitions of a provider (language) pack - see [Extending](extending.md).

The replacement is used everywhere - also as a dependency of other extensions:

```php
use Quidam\Core\Person;
use Quidam\Definitions\Enum\Gender;

class MyPerson extends Person
{
    public function lastName(?Gender $gender = null): string
    {
        return 'Kowalski';
    }
}

$custom = $generator->withDefinition(PersonExtensionInterface::class, MyPerson::class);

$custom->person()->name();      // 'Brenda Kowalski'
$custom->internet()->email();   // 'brenda_kowalski@example.org'
$custom->company()->company();  // 'Kowalski Group'
$generator->person()->name();   // original generator is not changed
```

A replacement either extends the default class (override only what changes - usually data constants, see [Extending](extending.md)) or implements the interface from scratch.

Every `with*()` starts with fresh instances - nothing is carried over from the previous generator except objects given as definitions - so there are no stale dependencies: replacing the randomizer affects all extensions.

## Own accessors - extending Quidam

Default extensions have accessors already. To have `$generator->car()` instead of `$generator->ext(Car::class)` for your own extension (or `text()` for an add-on), extend `Quidam`. It's a `readonly` class, so the child class has to be `readonly` too. `create()` and every `with*()` / `fork()` return `static`, so your class is kept:

```php
readonly class MyGenerator extends Quidam
{
    public function car(): Car
    {
        return $this->ext(Car::class);
    }
}

$generator = MyGenerator::create(seed: 1);
$generator->car()->brand();
$generator->withProvider(new PlProviderPack())->person()->name();
```

## Randomizers

All randomness goes through `Quidam\Definitions\Randomizer\RandomizerInterface`. Two implementations are registered:

| Container id | Default class | Engine | |
|---|---|---|---|
| `RandomizerInterface` | `XoshiroRandomizer` | `Random\Engine\Xoshiro256StarStar` - seeded with `create(seed: 42)`, otherwise with a random 256-bit seed from the OS | fast; repeatable when seeded |
| `SecureRandomizerInterface` | `SecureRandomizer` | `Random\Engine\Secure` | cryptographically secure - unpredictable, 2-8× slower |

Can two unseeded generators produce the same data? Only with the same seed - and the random seed is 256 bits from the OS (2²⁵⁶ ≈ 10⁷⁷ values). Even among 10¹² generators the chance that any two share a seed is ~10⁻⁵³. For comparison, `mt_rand()` (used by FakerPHP) has a 32-bit seed - two of ~77 000 processes share one with 50% probability. Repeated *values* (a name appearing twice) come from the size of data lists, not the engine - use `unique()` / `sequence()`.

### Secure randomness

Dummy data doesn't need cryptographic randomness, so the default is fast. When values must be **unpredictable** - passwords or API keys of real test accounts on a shared staging environment, tokens that must not be guessable - use the secure randomizer:

```php
$secure = Quidam::createSecure();                   // or createSecure(clock: $clock)
$secure = $generator->secure();                         // an existing generator, keeps its definitions

$secure->internet()->password(16, 24);
$secure->hash()->apiKey('sk_test_');
$secure->fork('admin')->hash()->base64Url();   // forks and with*() stay secure
```

`secure()` points `RandomizerInterface` to whatever `SecureRandomizerInterface` resolves to in this generator - so "secure" itself is replaceable (a hardware module, an audited library, a fake one in tests):

```php
$generator->withDefinition(SecureRandomizerInterface::class, MyHsmRandomizer::class)->secure();
Quidam::createSecure(definitionPack: new MyDefinitionPack());   // pack registers its own SecureRandomizerInterface
```

* every random value is a system call - see [Performance](performance.md); keep a separate secure generator for the few values that need it, not for whole fixture sets
* there's no seed - secure values are never repeatable
* `$generator->random() instanceof SecureRandomizerInterface` tells which kind a generator uses

### Own randomizer

`CoreRandomizer` wraps PHP `Random\Randomizer` - a randomizer on another engine is a constructor:

```php
use Quidam\Core\Randomizer\CoreRandomizer;
use Quidam\Core\Randomizer\XoshiroRandomizer;
use Random\Engine\PcgOneseq128XslRr64;

final class PcgRandomizer extends CoreRandomizer
{
    public function __construct(int $seed)
    {
        parent::__construct(new PcgOneseq128XslRr64($seed));
    }
}

$pcg = $generator->withDefinition(RandomizerInterface::class, new PcgRandomizer(42));

// or change behaviour, i.e. for boundary tests
final class AlwaysLowestRandomizer extends XoshiroRandomizer
{
    public function getInt(int $min, int $max): int
    {
        return $min;
    }
}

$lowest = $generator->withDefinition(RandomizerInterface::class, new AlwaysLowestRandomizer());
$lowest->color()->hexColor();   // '#000000'
```

Randomness from other sources implements `RandomizerInterface` directly. The randomizer's methods (`randomElement()`, `weightedElement()`, `shuffleElements()`...) are listed in [Random](extensions/random.md).

## Clock

Extensions which depend on "now" use PSR-20 `Psr\Clock\ClockInterface`:

| Extension | Uses clock for |
|---|---|
| `DateTime` | "now", relative dates (`'-30 days'`, `'tomorrow'`), result timezone, `dateOfBirth()` age |
| `Uuid` | timestamp part of `uuid7()` and `ulid()` |
| `Payment` | credit card expiration dates |
| `UserAgent` | estimating current browser versions |
| `Version` | date build identifier in `semver(build: true)` |
| `Hash`, `Profile` | `jwt()` issued / expiry times, age matching the birth date |

Default `Quidam\Clock\SystemClock` returns current time in the default PHP timezone or the one given:

```php
$generator = Quidam::create(clock: new SystemClock('America/New_York'));
$generator->dateTime()->dateTimePast()->getTimezone()->getName(); // 'America/New_York'
```

For tests use `Quidam\Clock\FrozenClock` - "now" never moves:

```php
$frozen = new FrozenClock('2026-10-08 12:00:00 Europe/Warsaw');     // or new FrozenClock($dateTime)

$generator = Quidam::create(seed: 2026, clock: $frozen);
$generator->dateTime()->dateOfBirth(18, 18)->format('Y-m-d'); // between '2007-10-09' and '2008-10-08'
```

Any other PSR-20 clock works too - i.e. Symfony `MockClock` or the framework's clock, so time travel in tests moves generated dates too (see [Integrations](integrations.md)).

## Locale

Country conventions - date and price formats, currency, phone calling code, the default country of IBANs and timezones - come from one `LocaleInterface` service. Default is international sample data without a country (ISO dates, EUR, `'1,234.56 EUR'`); a language pack replaces it. See [Locale](locale.md).

## Seed and repeatable data

With the same seed, the same calls in the same order return the same values:

```php
$first = Quidam::create(seed: 123);
$second = Quidam::create(seed: 123);

$first->person()->name() === $second->person()->name(); // true
```

Things to know:

* values depend on **call order** - one extra call shifts all following values
* date-related values (relative dates, `uuid7()`, card expiration...) also depend on the clock - use a frozen clock for fully repeatable data
* every `with*()` creates a fresh randomizer from the seed, so it starts the sequence again
* use [forks](#forks---stable-records) when adding a call shouldn't shift the values of other records
* the container doesn't matter - the built-in one and any [adapter](containers.md) give the same values

### Seed stability

`test/Seed/SeededOutputTest.php` records the output of every public extension method, the randomizer, strategies, forks and a language pack for three seeds - a change in seeded output can't happen by accident; intended changes regenerate the snapshot (`composer seed-snapshot`).

The promise, from version 0.0.1:

* **patch release** (0.0.1 → 0.0.2) - seeded output doesn't change: the same seed, clock and calls give the same values
* **minor or major release** - seeded output may change (i.e. a data list got new names, a generator was fixed); every change is listed in the [changelog](../CHANGELOG.md) under **Seeded output changes**

The included [pl_PL pack](../src/LanguagePack/pl_PL/README.md) keeps the same promise with its own snapshot.

Not covered: generators without seed (random by design), your own randomizer, replaced extensions or overridden data.

#### The same guard for your extensions and packs

Language packs and own extensions can keep the same promise with `Quidam\Testing\SeedSnapshot` - the class Quidam's own snapshot test uses. It works with any test framework:

```php
use Quidam\Clock\FrozenClock;
use Quidam\Testing\SeedSnapshot;

$clock = new FrozenClock('2026-01-15 10:30:00 UTC');
$snapshot = new SeedSnapshot(
    __DIR__ . '/snapshot.json',
    fn (int $seed) => Quidam::create($seed, $clock)->withProvider(new PlProviderPack()),
);
$calls = [
    [PersonExtensionInterface::class, 'name', []],
    [PhoneNumberExtensionInterface::class, 'internationalPhoneNumber', []],
    [MyNationalIdInterface::class, 'pesel', []],              // your pack's own extension
];
$output = $snapshot->generate($calls, [
    'citizen' => fn (Quidam $g) => $g->profile()->person(),   // named scenarios
]);

if (getenv('UPDATE_SEED_SNAPSHOT') === '1') {
    $snapshot->save($output);                           // after an intended change - list it in your changelog
} else {
    self::assertSame([], $snapshot->changed($output));  // labels of changed values, i.e. "42 Person::name()"
}

// no public method can skip the snapshot
self::assertSame([], SeedSnapshot::uncovered([MyNationalIdInterface::class], $calls));
```

Every call runs on a fresh generator for each seed (1, 42, 2026 by default), twice - so a changed value points to the method which changed. Labels use short names (`Person::name()` for `PersonExtensionInterface`), scenarios `42 Quidam citizen`; values are normalized to JSON - dates as ISO strings, enums as `Class::Case`, long or binary strings as a hash.

## Forks - stable records

With one seed, values depend on call order: adding a field to the first user changes every user after it, and fixture snapshots break for unrelated reasons. `fork($key)` returns a generator with its own random stream, seeded from the generator's seed and the key - so a record depends only on the seed and its key:

```php
$generator = Quidam::create(seed: 2026);

foreach (range(1, 100) as $id) {
    $g = $generator->fork('users.' . $id);            // or fork($id)

    $users[] = [
        'id' => $id,
        'name' => $g->person()->name(),        // the same for users.42 on every run...
        'email' => $g->internet()->email(),    // ...also after adding fields to other users,
    ];                                                                     // or changing the number of users
}
```

* the same seed and key always give the same values - no matter what was generated before or how many forks exist
* forking doesn't use the parent's random stream - the parent continues as if the fork didn't happen
* forks can be nested - `fork('users.1')->fork('address')` is stable too
* without seed, forks are random as well (nothing to derive from)
* extensions of a fork are fresh, like after `with*()`; **unique memory and sequences are shared** with the parent, so `unique('users.email', ...)` stays unique across forks
* the derived seed comes from the `Seed` service - a randomizer registered as an object (`withDefinition(RandomizerInterface::class, $object)`) is shared as is and not forked; pass the seed to `create()`
* secure generators stay secure in forks

Values inside one fork still depend on call order - adding a field to a record changes the values of fields after it in **that** record. For field-level stability fork per field: `$g->fork('email')->internet()->email()`.

## Container

The built-in `Quidam\Container\SimpleContainer` (~160 lines) is an immutable PSR-11 container:

* **definitions**: class name (autowired, alias when it differs from the id), `Closure` (factory, parameters resolved by type), object or any other value (as is)
* **autowiring**: any existing class can be fetched without definition - constructor parameters are resolved by type; scalar parameters use their default value, nullable ones `null`
* **singletons**: each entry is created once per container; an alias and its class share the instance
* **self injection**: a parameter typed `ContainerInterface` / `QuidamContainerInterface` receives the container
* **circular dependencies** are detected and reported (`A -> B -> A`)
* `withDefinitions()` returns a new container - resolved instances are not carried over

`Quidam` uses it only through `ContainerFactoryInterface` / `QuidamContainerInterface`, so another container (PHP-DI ...) can be plugged in with an adapter - see [Containers](containers.md). `$generator->container()` returns the container of a generator (for frameworks and tests).

## What can be replaced

Everything is a definition under an interface id:

| Part | Id | How |
|---|---|---|
| Extensions | `*ExtensionInterface` | subclass of the default (`MyPerson extends Person`) or own implementation - see [Extending](extending.md) |
| Extension data | - | override `protected const` lists in a subclass (language packs) |
| Locale (country conventions) | `LocaleInterface` | `new Locale(...)` with your values, or own implementation - see [Locale](locale.md) |
| Randomizer | `RandomizerInterface`, `SecureRandomizerInterface` | own implementation - see [Randomizers](#randomizers) |
| Clock | `Psr\Clock\ClockInterface` | any PSR-20 clock - `create(clock: ...)` or `withDefinition()` |
| Calculators | `LuhnCalculatorInterface`, `EanCalculatorInterface`, `IsbnCalculatorInterface`, `IbanCalculatorInterface` | own implementation - see [Calculators](extending.md#calculators) |
| Transliterator | `TransliteratorInterface` | own rules - see [Transliterator](extensions/transliterator.md) |
| Add-ons | `TemplateInterface`, `FillerInterface`, `TextInterface`, `RichTextInterface`, `EdgeCasesInterface` | subclass (own rules, corpus, cases) or own implementation - see [index](index.md#add-ons) |
| Generator-wide strategy | `StrategyInterface` | `withStrategy()` - see [Strategies](strategies.md) |
| Unique memory, sequences | `UniqueMemory`, `SequenceMemory` | object or subclass with `withDefinition()` |
| All defaults at once | - | `DefinitionPackInterface` - `create(definitionPack: ...)` |
| Container | - | `ContainerFactoryInterface` adapter - `create(containerFactory: ...)` |
| Quidam | - | `readonly` subclass - own accessors |

## Exceptions

All exceptions implement `Quidam\Exception\QuidamExceptionInterface` - except PHP's own `Error`s from bugs, i.e. inside a [Template](addons/template.md) call:

| Exception | When |
|---|---|
| `Quidam\Exception\InvalidArgumentException` | invalid argument, i.e. `numberBetween(10, 1)`, unknown card type, invalid `Locale` value |
| `Quidam\Exception\OverflowException` | `unique()` / `valid()` didn't find a value within retry limit |
| `Quidam\Exception\DefinitionNotFoundException` | `ext()` of an unknown id which can't be autowired (PSR-11 `NotFoundExceptionInterface`) |
| `Quidam\Exception\ContainerException` | entry can't be built - unresolvable parameter, circular dependency, not instantiable, or resolves to an object of a different type than the id (PSR-11 `ContainerExceptionInterface`) |
| `Quidam\Exception\RuntimeException` | the environment failed, not the arguments - i.e. a memory stream can't be opened |

```php
use Quidam\Exception\QuidamExceptionInterface;

try {
    $generator->number()->numberBetween(10, 1);
} catch (QuidamExceptionInterface $e) {
    echo $e->getMessage(); // '$min cannot be higher than $max'
}
```

## Architecture

```
Quidam       - person(), address(), ... (ExtensionAccessorsTrait), ext(), random(), with*(), secure(), fork(), many(),
 │                 sequence(), optional(), unique(), valid(), strategy(), generate()
 └─ QuidamContainerInterface  - immutable, built by ContainerFactoryInterface (SimpleContainer by default)
     ├─ Seed                          - seed of the default randomizer (value, or random), derived per key by fork()
     ├─ RandomizerInterface           XoshiroRandomizer (secure(): -> SecureRandomizerInterface)
     ├─ SecureRandomizerInterface     SecureRandomizer (Random\Engine\Secure)
     ├─ ClockInterface                SystemClock, FrozenClock, or any PSR-20 clock
     ├─ LocaleInterface               Locale - country conventions shared by extensions
     ├─ TransliteratorInterface       Transliterator - ASCII for user names, emails, domains
     ├─ StrategyInterface             SimpleStrategy - generator-wide strategy of generate()
     ├─ *CalculatorInterface          Luhn, Ean, Isbn, Iban - check digits
     ├─ *ExtensionInterface (24)      Core\* - depend on each other through interfaces (Internet uses Person...)
     ├─ add-ons (opt-in, lazy)        Addon\* - Template, Filler, Text, RichText, EdgeCases
     ├─ UniqueMemory                  - values remembered by unique(), per generator (shared with forks)
     └─ SequenceMemory                - counters of sequence(), per generator (shared with forks)
```

The ids and default classes are in `Quidam\DefinitionPack\DefinitionPack` - `coreDefinitions()`, `calculators()`, `extensions()`, `addons()`. Outside the container: `Quidam\Testing\SeedSnapshot` (seed snapshot tests) and `Quidam\Cli\Application` (`bin/quidam`, see [Command line](cli.md)).
