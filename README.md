# QuidamPHP

QuidamPHP (`Quidam` in code) is a modern take on fake / dummy data generation for PHP, built around three goals:
- **Proper architecture.** Every part sits behind an interface in an immutable container: extensions, randomizers, locale, check-digit calculators, clock, strategies and even the container itself. Dependencies are injected, never looked up by magic, so anything can be replaced in one place, and the replacement is used everywhere.
- **Speed.** Values are 2-6× faster than FakerPHP, a new instance costs 0.4 µs (FakerPHP: 20 µs), and startup is about 3 ms. Creating a fresh generator for every test is cheap.
- **Modern PHP.** It requires PHP 8.4 or newer and uses what modern PHP offers: readonly classes, typed class constants, the `Random\Randomizer` engines and PSR-20 clocks.

It also brings:
- a fully typed API that PHPStan checks at max level, with no annotations needed
- only two small PSR dependencies
- repeatable data you can rely on: per-generator seeds, stable records with `fork()`, frozen clocks
- neutral, country-less defaults, ready for language packs - a complete Polish pack is included

It keeps the architecture of [DummyGenerator](https://github.com/johnykvsky/dummygenerator) - interfaces in a container, language providers, strategies - **without the magic**: no `__call()`, no string templates in the core, no PHP-DI dependency. English words by default, country conventions in one replaceable locale service.

## The name

*Quidam* is Latin for "a certain someone". In English it means an unnamed, ordinary person, a nobody in particular. That's exactly what the library makes: people who look real but aren't anyone.

Pronounced **KWEE-dahm** (/ˈkwiː.dam/), as in Latin. The anglicized **KWY-dəm** (/ˈkwaɪ.dəm/) is fine too.

And since the name is Latin, there's a [Latin language pack](src/LanguagePack/la_VA/README.md) too: `vendor/bin/quidam -l la_VA person.name` gives you a Roman citizen, `longDate()` dates in the Roman calendar.

# Installation

```shell
composer require johnykvsky/quidam --dev
```

Requires PHP >= 8.4 (tested on 8.4 and 8.5). Dependencies: `psr/clock`, `psr/container` - the container is built in.

# Usage

```php
use Quidam\Quidam;

$generator = Quidam::create();                 // or create(seed: 2026) for repeatable data

$generator->person()->name();     // 'Brenda Patterson'
$generator->internet()->email();  // 'brenda_patterson@example.org'

$person = $generator->person();    // or keep the extension
$person->firstName();

$email = $generator->unique('users.email', fn (Quidam $g) => $g->internet()->email());
$generator->fork('users.42')->person()->name();   // stable record
```

Accessors (`person()`, `internet()`, ...) are typed shortcuts of `ext(PersonExtensionInterface::class)` - every method is a real method, IDE autocompletion and PHPStan work. Add-ons and your own extensions use `ext()`, which is generic (`class-string<T>` → `T`).

**Full documentation: [docs/index.md](docs/index.md)** - the generator, [locale](docs/locale.md), [extending and language packs](docs/extending.md), [strategies](docs/strategies.md), [containers](docs/containers.md) and every extension with examples.

**Coming from FakerPHP: [why switch](#why-switch-from-fakerphp), [comparison](docs/comparison.md) and [migration guide](docs/migration.md)** - also from DummyGenerator.

**PHPUnit, Laravel, Symfony, Doctrine fixtures, Foundry, Slim: [docs/integrations.md](docs/integrations.md)**

**Command line: `vendor/bin/quidam internet.email -n 5` - [docs/cli.md](docs/cli.md)**

## Why switch from FakerPHP?

Quidam does everything Faker is used for in tests, and does it better: faster, typed, reproducible and safe by default. Faker works - until a seeded test turns flaky, a fixture changes because someone added a field, a typo in `$faker->emial` reaches CI, or a test suite spends seconds creating generators. Quidam fixes those at the root:

**1. Seeded data stays the same - really.** Faker keeps its random state in PHP's global `mt_rand()`: any `rand()`, `shuffle()` or `array_rand()` call in your code or a library changes what a seeded Faker returns next, and `MT_RAND_PHP` (its seeding mode) is deprecated since PHP 8.3. Every Quidam generator has its own random engine:

```php
$generator = Quidam::create(seed: 42);   // its own Xoshiro256** - nothing else can move it
```

**2. Adding a field doesn't change every other record.** With one seed, values depend on call order - add `phone` to the first user and every user after it changes, and so do your snapshots. A fork depends only on the seed and its key:

```php
foreach ($ids as $id) {
    $user = $generator->fork('users.' . $id);   // user 42 is always the same user
    $users[] = ['name' => $user->person()->name(), 'email' => $user->internet()->email()];
}
```

**3. Typed API instead of magic.** `$faker->name` is found by name at runtime. `$generator->person()->name()` is a real method: autocompletion, "find usages", refactoring and PHPStan (max level, no annotations) work, and a typo fails in your IDE, not in CI.

**4. Faster everywhere it matters** - default mode, median per call ([details](docs/performance.md)):

| | FakerPHP | Quidam | |
|---|---|---|---|
| new generator | 20.3 µs | 0.4 µs | **50×** |
| startup (fresh process, first value) | 5.7 ms | 3.3 ms | 1.7× |
| `name()` | 4.2 µs | 0.85 µs | 4.9× |
| `email()` | 11.5 µs | 1.8 µs | 6.4× |
| `address()` | 15.7 µs | 4.2 µs | 3.7× |
| 8-field user record | 66 µs | 24 µs | 2.8× |
| unique email (1000 per scope) | 18.6 µs | 6.1 µs | 3.0× |
| IBAN, card number | 5.8 / 3.7 µs | 6.2 / 3.8 µs | equal |

A fresh generator per test - no shared state between tests - costs nothing worth measuring.

**5. Test data that can't hurt anyone.** `email()` is always a reserved `example.*` domain (Faker's `email()` uses real providers and generated domains); IPv4 / IPv6 are public, MAC addresses locally administered; IBANs, cards, EANs, ISBNs and VINs have valid check digits and real prefixes - they pass real validators, and check-digit calculators are services you can swap for invalid ones in negative tests. Measured against Faker's generators: [docs/data-quality.md](docs/data-quality.md).

**6. Time you control.** Dates, ages, `uuid7()` and `ulid()` use a PSR-20 clock - freeze it, or pass your framework's clock, and time travel in tests applies to generated data too:

```php
$generator = Quidam::create(seed: 1, clock: $frozenClock);   // the same birth dates every run
```

**7. No hidden shared state.** Generators are immutable - `withDefinition()`, `withProvider()` and friends return a new one, so a test or a factory can't change data for everyone else. `unique()` takes your key (`'users.email'`), so two factories filling one column share uniqueness.

**8. Data that doesn't pretend to be American.** Faker's default is `en_US` - `'(512) 555-0199'`, `'$1,234.56'`, `'10/09/2026'`. Quidam's defaults are visibly neutral (`'034 062 0174'`, `'1,234.50 EUR'`, `'2026-10-09'`), and one language pack switches every convention at once - see the included [pl_PL](src/LanguagePack/pl_PL/README.md).

### Switching takes minutes

| FakerPHP | Quidam |
|---|---|
| `$faker = Faker\Factory::create(); $faker->seed(42);` | `$generator = Quidam::create(seed: 42);` |
| `$faker->name()` / `$faker->name` | `$generator->person()->name()` |
| `$faker->email()` / `$faker->safeEmail()` | `$generator->internet()->email()` |
| `$faker->unique()->email()` | `$generator->unique('users.email', fn (Quidam $g) => $g->internet()->email())` |
| `$faker->optional()->sentence()` | `$generator->optional(fn (Quidam $g) => $g->lorem()->sentence())` |
| `$faker->dateTimeBetween('-1 year')` | `$generator->dateTime()->dateTimeBetween('-1 year')` |
| `$faker->numerify('###-###')` | `$generator->strings()->numerify('###-###')` |

Every Faker method has its counterpart in the [migration guide](docs/migration.md); Laravel factories, Symfony, Doctrine fixtures, Foundry and Slim are wired in [docs/integrations.md](docs/integrations.md).

**Faker's one advantage: more locales.** Faker ships 75 locales, Quidam one complete pack (pl_PL). A Faker locale is mostly data - name lists, street names, phone formats - and it ports to Quidam as `protected const` arrays in subclasses of the core extensions; pl_PL started exactly that way, from Faker's pl_PL. See [Override data - language packs](docs/extending.md#override-data---language-packs).

Images aren't a reason to stay: Faker's own `image()` / `imageUrl()` are deprecated since Faker 1.20 and removed in Faker 2. Quidam requires PHP 8.4 or newer.

### All differences in one table

| | FakerPHP | Quidam | Why it matters |
|---|---|---|---|
| **API** | magic `$faker->name` / `__call()`, methods found by name at runtime | typed extensions: `person()->name()` | autocompletion, "find usages", refactoring and PHPStan work without annotations; a typo fails in the IDE, not in CI |
| **Providers** | one method namespace - providers with the same method shadow each other | separate objects under interfaces, depending on each other through them | no name collisions; replace `Person` once and emails, companies and streets use your names |
| **Mutability** | `addProvider()`, `seed()` change a shared object | immutable - `with*()` returns a new generator | a test or factory can't change data for everyone else |
| **Randomness** | global `mt_rand()`, 32-bit seed shared by the whole process, `MT_RAND_PHP` deprecated since PHP 8.3 | own `Xoshiro256**` per generator, 256-bit random seed when unseeded, secure engine on demand | seeded tests don't break when other code calls `mt_rand()`; no deprecation notices; unpredictable values when needed |
| **Stable records** | one sequence - adding a field shifts every later value | `fork('users.42')` - a record depends only on the seed and its key | fixture snapshots don't change for unrelated reasons |
| **Dates** | system time | PSR-20 clock | frozen or framework clocks make relative dates, ages and `uuid7()` repeatable; time travel in tests applies |
| **Unique values** | scope is the method name | scope is your key (`'users.email'`), plus generator-wide strategies | two factories writing one column, or two methods producing one kind of value, share uniqueness |
| **Emails** | `email()` can use real domains (`gmail.com`, generated `smith.com`) | `email()` always reserved `example.*` | test data can't reach a real inbox |
| **Defaults** | `en_US`: `'(512) 555-0199'`, `'$1,234.56'`, `'10/09/2026'`, US states | neutral: `'034 062 0174'`, `'1,234.50 EUR'`, `'2026-10-09'`, generic regions | non-US apps aren't tested against US-shaped data that pretends to be real |
| **Locales** | 75, conventions mixed into each provider | neutral core + one included pack (pl_PL); one `LocaleInterface` (currency, formats, calling code, country) + overridable data, transliterated emails | a language pack switches all conventions consistently with one definition |
| **Check digits** | inside providers | services (`LuhnCalculatorInterface`...) with `isValid()` | invalid cards / IBANs / barcodes for negative tests with one definition; validation in your code |
| **Data quality** | | real GS1 / ISBN prefixes, VINs, Bitcoin / Ethereum checksums, current user agents, coherent `Profile` | values pass real validators and look current |
| **Speed** | | 2-6× faster per value, startup 3.3 vs 5.7 ms, new instance 0.4 vs 20 µs | cheap to create one generator per test |

Templates, `realText()` and `randomHtml()` are opt-in add-ons in Quidam. Details: [docs/comparison.md](docs/comparison.md).

## Quidam vs DummyGenerator

Quidam keeps DummyGenerator's architecture - container with interface ids, `DefinitionPack`, `ProviderPackInterface`
+ `withProvider()`, `withDefinition()`, `ext()`, calculators as services, the `StrategyInterface` contract - and removes what made it slow and hard to analyse:

| DummyGenerator | Quidam | Why it matters |
|---|---|---|
| `$generator->name()` through `__call()`, method looked up in every extension | `person()->name()` - a real method call | typed API; names 28× faster |
| `parse('{{ firstName }}')`; extensions build values from `{{ }}` formats | removed - extensions call each other directly | lorem text ~500× faster, `url()` ~350× |
| extensions get `GeneratorInterface` (`GeneratorProxy`) to call other extensions | extensions get what they need **by interface** in the constructor | real dependency injection - dependencies are visible and replaceable |
| default randomizer `Random\Engine\Secure` | `XoshiroRandomizer` (seeded or random seed); `SecureRandomizerInterface` on demand | fast by default, secure when needed - and both replaceable |
| strategy applied to every magic call, method name is the unique key | `generate($name, fn)`, shortcuts and chains; explicit keys | `unique()` works - DummyGenerator stalled at 40-50 emails in 10 s |
| PHP-DI dependency, mutable container | built-in immutable container; others through [adapters](docs/containers.md) | startup 3.3 vs 8.1 ms, a new instance 0.4 vs 26 µs |
| 30 extensions in groups | 24 typed extensions, `LocaleInterface`, `TransliteratorInterface` | more and better data, consistent country conventions |
| `SystemClockInterface` | PSR-20 `ClockInterface` | framework clocks work as they are |

Migration: [docs/migration.md#from-dummygenerator](docs/migration.md#from-dummygenerator).

## Performance

* 2-6× faster than FakerPHP for names, emails, addresses and text, a typical 8-field user record 24 µs (FakerPHP 66 µs, DummyGenerator 45-129 µs)
* startup 3.3 ms, a new instance 0.4 µs - cheap to create one per test
* 1000 unique emails 6.1 µs per email (FakerPHP 19 µs, DummyGenerator stalls)

Details, method and the PHP-DI comparison: [docs/performance.md](docs/performance.md).

## Extensions

| Container id | Methods |
|---|---|
| [`AddressExtensionInterface`](docs/extensions/address.md) | `cityPrefix`, `citySuffix`, `city`, `streetName`, `streetSuffix`, `secondaryAddress`, `state`, `stateCode`, `stateData`, `buildingNumber`, `streetAddress`, `postcode`, `address`, `county`, `cityData`, `addressData`, `country`, `countryCode`, `countryCodeAlpha3`, `countryData`, `latitude`, `longitude`, `coordinates` |
| [`BarcodeExtensionInterface`](docs/extensions/barcode.md) | `ean13`, `ean8`, `upcA`, `isbn13`, `isbn10`, `ismn` |
| [`BloodExtensionInterface`](docs/extensions/blood.md) | `bloodType`, `bloodRh`, `bloodGroup` |
| [`ColorExtensionInterface`](docs/extensions/color.md) | `hexColor`, `safeHexColor`, `rgbColorAsArray`, `rgbColor`, `rgbCssColor`, `rgbaCssColor`, `hslColorAsArray`, `hslColor`, `hslCssColor`, `safeColorName`, `colorName` |
| [`CommerceExtensionInterface`](docs/extensions/commerce.md) | `productName`, `productAdjective`, `productMaterial`, `product`, `department`, `sku`, `promotionCode` |
| [`CompanyExtensionInterface`](docs/extensions/company.md) | `company`, `companySuffix`, `jobTitle`, `industry`, `catchPhrase` |
| [`DateTimeExtensionInterface`](docs/extensions/date-time.md) | `dateTime`, `dateTimeBetween`, `dateTimeRange`, `dateTimesBetween`, `dateTimePast`, `dateTimeFuture`, `dateOfBirth`, `date`, `time`, `unixTime`, `monthName`, `dayOfWeek`, `localDate`, `localDateTime`, `longDate`, `century`, `timezone` |
| [`EnumExtensionInterface`](docs/extensions/enum.md) | `enumCase`, `enumValue` |
| [`FileExtensionInterface`](docs/extensions/file.md) | `mimeType`, `extension`, `fileName`, `mimeTypeForExtension`, `directoryPath`, `filePath`, `csv`, `json`, `fileSize`, `fileSizeFormatted` |
| [`HashExtensionInterface`](docs/extensions/hash.md) | `md5`, `sha1`, `sha256`, `sha512`, `base64`, `base64Url`, `jwt`, `apiKey` |
| [`InternetExtensionInterface`](docs/extensions/internet.md) | `email`, `freeEmail`, `companyEmail`, `safeEmailDomain`, `freeEmailDomain`, `userName`, `password`, `domainName`, `domainWord`, `tld`, `url`, `protocol`, `hostname`, `hashtag`, `socialHandle`, `urlPath`, `slug`, `ipv4`, `ipv4Cidr`, `localIpv4`, `ipv6`, `ipv6Cidr`, `macAddress`, `port`, `httpMethod`, `httpStatusCode` |
| [`LanguageExtensionInterface`](docs/extensions/language.md) | `language`, `languageCode`, `languageData`, `locale` |
| [`LoremExtensionInterface`](docs/extensions/lorem.md) | `word`, `words`, `sentence`, `sentences`, `paragraph`, `paragraphs`, `text` |
| [`NumberExtensionInterface`](docs/extensions/number.md) | `numberBetween`, `randomDigit`, `randomDigitNotZero`, `randomNumber`, `randomFloat`, `gaussian`, `gaussianInt`, `percentage`, `boolean` |
| [`PaymentExtensionInterface`](docs/extensions/payment.md) | `creditCardType`, `creditCardNumber`, `maskedCreditCardNumber`, `creditCardExpirationDate`, `creditCardCvv`, `creditCardDetails`, `iban`, `swiftBicNumber`, `currencyCode`, `currencyName`, `currencySymbol`, `currencyData`, `price`, `priceInMinorUnits`, `localCurrency`, `localPrice`, `formatPrice`, `currencyDecimals`, `bankAccountNumber`, `transactionType`, `bitcoinAddress`, `ethereumAddress` |
| [`PersonExtensionInterface`](docs/extensions/person.md) | `name`, `firstName`, `middleName`, `age`, `initials`, `suffix`, `lastName`, `title`, `gender` |
| [`PhoneNumberExtensionInterface`](docs/extensions/phone-number.md) | `phoneNumber`, `mobilePhoneNumber`, `internationalPhoneNumber`, `e164PhoneNumber`, `imei` |
| [`ProfileExtensionInterface`](docs/extensions/profile.md) | `person` |
| [`StringsExtensionInterface`](docs/extensions/strings.md) | `string`, `numerify`, `lexify`, `bothify`, `hexadecimal`, `regexify` |
| [`SystemExtensionInterface`](docs/extensions/system.md) | `cronExpression`, `logLevel`, `environmentVariable` |
| [`UserAgentExtensionInterface`](docs/extensions/user-agent.md) | `userAgent`, `chrome`, `edge`, `opera`, `firefox`, `safari`, `botUserAgent` |
| [`UuidExtensionInterface`](docs/extensions/uuid.md) | `uuid4`, `uuid3`, `uuid5`, `nanoid`, `snowflake`, `uuid7`, `ulid` |
| [`VehicleExtensionInterface`](docs/extensions/vehicle.md) | `vehicle`, `manufacturer`, `model`, `type`, `fuel`, `vin`, `licensePlate` |
| [`VersionExtensionInterface`](docs/extensions/version.md) | `semver`, `gitCommitHash`, `gitBranchName`, `gitCommitMessage`, `semverConstraint` |

Plus `$generator->random()` (`RandomizerInterface`): `getInt`, `getFloat`, `getBool`, `getBytes`, `getBytesFromString`, `randomLetter`, `randomElement`, `randomElements`, `randomKey`, `weightedElement`, `shuffleElements` - and `TransliteratorInterface`: `transliterate`, `slug`.

Not in the core: country-only generators like `tollFreePhoneNumber()` or US `bankRoutingNumber()` - they belong to language packs (pl_PL has `tollFreePhoneNumber()`).

## Add-ons and tools

Opt-in - registered under interfaces, nothing is loaded until you ask for one:

```php
$generator->ext(TemplateInterface::class)->parse('{{ person.name }} <{{ internet.email }}>');   // formats stored as data
$generator->ext(FillerInterface::class)->make(UserDto::class);      // DTO filled by property names and types
$generator->ext(TextInterface::class)->realText(100, 200);          // realistic text from a book (Markov chain)
$generator->ext(RichTextInterface::class)->html();                  // randomHtml() - also markdown()
$generator->ext(EdgeCasesInterface::class)->string('unicode');      // strings that break code
```

* **Command line** - `vendor/bin/quidam internet.email -n 5 --unique`, templates, JSON / CSV, language packs (`-l pl_PL`) ([docs/cli.md](docs/cli.md))
* **SeedSnapshot** - `Quidam\Testing\SeedSnapshot` guards seeded output of your packs and extensions ([docs](docs/quidam.md#the-same-guard-for-your-extensions-and-packs))

Details: [docs/index.md#add-ons](docs/index.md#add-ons).

## Strategies

```php
// one strategy
$generator->unique('users.email', fn (Quidam $g) => $g->internet()->email());
$generator->optional(fn (Quidam $g) => $g->lorem()->sentence(), 30);

// mixed - first added is the outermost one
$generator->strategy()->chance(70)->unique('contacts.phone')->add(new MyStrategy())
    ->generate(fn (Quidam $g) => $g->phoneNumber()->e164PhoneNumber());

// generator-wide, DummyGenerator style - $name is the key
$generator->withStrategy(UniqueStrategy::class)->generate('users.email', fn (Quidam $g) => ...);
```

Own strategies implement DummyGenerator's `StrategyInterface` (`generate(string $name, callable $callback)`) - see [docs/strategies.md](docs/strategies.md).

## Language packs

The core has no locale - defaults are neutral, languages come as packs. One complete pack is included, [pl_PL](src/LanguagePack/pl_PL/README.md), as an example of how to write one:

```php
use Quidam\LanguagePack\pl_PL\PlPlGenerator;

$pl = PlPlGenerator::create(seed: 2026);

$pl->person()->name();                     // 'Kazimiera Wójtowicz' - surnames follow gender
$pl->address()->address();                 // 'os. Toruńska 22, 71-220 Bezrzecze' - real place, postcode, county
$pl->phoneNumber()->e164PhoneNumber();     // '+48788228453'
$pl->payment()->iban(formatted: true);     // 'PL43 2728 8223 8453 1540 8939 7198' - real bank code
$pl->nationalId()->pesel();                // '51071882277'
$pl->profile()->citizen();                 // one person: PESEL matches birth date and gender, address of one place
```

The pack is one `PlPlProviderPack` - a `Locale` with country conventions and pack classes under core interfaces - so it works on any generator: `Quidam::create()->withProvider(new PlPlProviderPack())`.

See [docs/locale.md](docs/locale.md) and [docs/extending.md](docs/extending.md).

## Development

```shell
make check              # fix-style, check-style, phpstan (max level, src + test), phpunit
composer seed-snapshot  # after an intended change of seeded output
php bench/run.php       # benchmark against DummyGenerator and FakerPHP (sibling directories)
php bench/data-quality.php  # share of wrong / unsafe values, FakerPHP vs Quidam (docs/data-quality.md)
```
