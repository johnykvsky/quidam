# Quidam vs FakerPHP

What Quidam does differently from [FakerPHP](https://github.com/FakerPHP/Faker), what it adds - and what Faker has that Quidam doesn't. Faker facts refer to its main branch after v1.23.0, the version used in [Performance](performance.md). Generator by generator - what Faker gets wrong and how Quidam fixes it, measured - is on a separate page: **[Data quality - generators improved over FakerPHP](data-quality.md)**. To move code over, see the [Migration guide](migration.md).

* [At a glance](#at-a-glance)
* [API and design](#api-and-design)
* [Architecture - everything replaceable](#architecture---everything-replaceable)
* [Randomness and repeatable data](#randomness-and-repeatable-data)
* [Unique, optional, valid](#unique-optional-valid)
* [Locales and neutral data](#locales-and-neutral-data)
* [Data quality](#data-quality) - full list: [generators improved over FakerPHP](data-quality.md)
* [Only in Quidam](#only-in-quidam)
* [Performance](#performance)
* [What Faker still has](#what-faker-still-has)

## At a glance

| | FakerPHP | Quidam |
|---|---|---|
| API | magic `__get()` / `__call()` - `$faker->email` | typed extensions - `$generator->internet()->email()` |
| IDE completion, PHPStan | via docblock annotations | native - typed accessors, every method is a real method, `ext()` is generic |
| Providers / extensions | classes added with `addProvider()`, found by method name | services under interfaces, depending on each other through them |
| Replacing a provider | add one with the same method name, later wins | `withDefinition(PersonExtensionInterface::class, MyPerson::class)` - used everywhere, also by dependent extensions |
| Instances | mutable | immutable - `with*()` returns a new generator |
| Random engine | global `mt_rand()` (`MT_RAND_PHP` variant when seeded) | per generator `Xoshiro256**`, replaceable; secure engine on demand |
| Seed | 32-bit, global for the whole process | 64-bit seed, 256-bit random state when unseeded; per generator |
| Stable records | - | `fork('users.42')` |
| Dates and "now" | system time | PSR-20 clock - frozen clock makes dates repeatable |
| `unique()` scope | method name | key you choose (`'users.email'`) |
| `email()` | generated or real free-provider domains | reserved `example.com/org/net` only |
| Locales | 75, data and conventions mixed in each provider | neutral core, one included pack ([pl_PL](../src/LanguagePack/pl_PL/README.md)) - `LocaleInterface` (conventions) + overridable data |
| Dependencies | none | `psr/clock`, `psr/container` |
| Data quality | values that fail validators: unassigned barcode prefixes, multicast MACs, weak passwords, floats out of range, 2014 user agents | real prefixes, valid formats, current data - [measured](data-quality.md) |
| Speed | | 2-9× faster for most values, instances ~17× cheaper |
| PHP | 7.4+ | 8.4+ |

## API and design

* **No magic.** Faker resolves `$faker->name` / `$faker->name()` at runtime by searching providers. Quidam fetches a typed extension by its interface - autocompletion, "find usages", refactoring and PHPStan (level max) work without annotations, and a typo is an error in your IDE, not at runtime.
* **Immutable.** `with*()` returns a new generator; nothing changes a shared object behind your back. In Faker, `addProvider()` and `seed()` change the generator (and `seed()` the whole process - see below).
* **No name collisions.** Faker's providers share one method namespace - two providers with `name()` shadow each other. Quidam extensions are separate objects: `ext(MyExtension::class)->name()` can't clash with `Person::name()`.
* **Extensions without registration.** Any class works with `$generator->ext(MyClass::class)`, constructor dependencies are autowired.
* **Templates are opt-in.** Faker's `parse('{{firstName}}')` can call any formatter by name, and is part of the core. Quidam's core has no `parse()` - build strings in PHP (`sprintf()`), where the IDE sees every call. For formats stored as data there's the [Template](addons/template.md) add-on: `{{ person.firstName }}` tokens resolved through an explicit, restrictable alias map - safe for templates from data.

## Architecture - everything replaceable

Faker's extension point is the provider. Quidam keeps every part behind an interface in a container:

| Part | Interface | Why it matters |
|---|---|---|
| extensions (24) | `PersonExtensionInterface`... | replace one, every extension using it follows - your `Person` names appear in emails, companies, streets |
| locale | `LocaleInterface` | one definition sets currency, price / date formats, phone calling code, IBAN and timezone country - consistent everywhere |
| randomizer | `RandomizerInterface`, `SecureRandomizerInterface` | own engine, edge-case randomizers for boundary tests, own "secure" source |
| check digits | `LuhnCalculatorInterface`, `IbanCalculatorInterface`, `EanCalculatorInterface`, `IsbnCalculatorInterface` | invalid cards / IMEIs / IBANs / barcodes for negative tests with one definition; validation in your code |
| transliteration | `TransliteratorInterface` | language-specific romanization for emails from non-Latin names |
| clock | PSR-20 `ClockInterface` | framework clocks - time travel in tests moves generated dates |
| strategy | `StrategyInterface` | generator-wide unique / chance / own behaviour |
| container | `ContainerFactoryInterface` | built-in by default, PHP-DI or others through an adapter |

This is the architecture of [DummyGenerator](https://github.com/johnykvsky/dummygenerator) - without its magic calls, templates and PHP-DI dependency. See [Quidam](quidam.md#what-can-be-replaced).

## Randomness and repeatable data

**Faker** uses PHP's global `mt_rand()`:

* `seed($n)` calls `mt_srand($n, MT_RAND_PHP)` - the **process-wide** generator: every Faker instance and any other code calling `mt_rand()` shares (and shifts) the same sequence
* `MT_RAND_PHP` is a biased, non-standard Mt19937 variant, **deprecated since PHP 8.3** - seeded Faker emits a deprecation notice on PHP 8.3+
* the seed is 32-bit - about 4.3 billion sequences; two of ~77 000 unseeded processes start the same sequence with 50% probability

**Quidam** gives every generator its own engine:

* seeded: `Xoshiro256**` from your seed; unseeded: `Xoshiro256**` with a 256-bit random seed - a repeated sequence is practically impossible (~10⁻⁵³ among 10¹² generators)
* `Quidam::createSecure()` / `->secure()` - `Random\Engine\Secure` (or your `SecureRandomizerInterface`) for values that must be unpredictable
* `fork('users.' . $id)` - a record depends only on the seed and its key, so adding a field or a record doesn't change other records ([Forks](quidam.md#forks---stable-records))
* dates come from a PSR-20 clock - with a frozen clock relative dates, ages, `uuid7()` and card expiry are repeatable too; Faker always uses system time
* a snapshot test of every public method guards seeded output ([Seed stability](quidam.md#seed-stability))

## Unique, optional, valid

| | FakerPHP | Quidam |
|---|---|---|
| unique | `$faker->unique()->email()` - scope is the method name | `$generator->unique('users.email', fn ($g) => ...)` - scope is your key, shared by any generator |
| reset | `unique(true)` resets all | `resetUnique('users.email')` or all; every `with*()` starts empty |
| optional | `optional(0.7)` - float weight; `optional(70)` divided by 100 with a deprecation | `optional(fn, 70)` or `optional(fn, 0.7)` - int is a percent, float a probability |
| valid | `$faker->valid($validator)->randomDigit()` | `$generator->valid($generator, $validator)` |
| combining | method chaining on proxy objects | `strategy()->chance(70)->unique('k')->valid(fn)->generate(fn)` - explicit order, chance short-circuits |
| generator-wide | - | `withStrategy(UniqueStrategy::class)` + `generate('key', fn)` |
| own strategies | - | `StrategyInterface` (DummyGenerator compatible) |

Keyed uniqueness matters when two factories write the same column, or a value is unique across generators (a login made from `userName()` in one place and from `email()` in another) - in Faker those are separate method scopes. Faker's `unique()` doesn't stall like DummyGenerator's did, but it's ~4× slower than Quidam's (see [Performance](performance.md)).

## Locales and neutral data

Faker has 75 locales - and its default `en_US` data is American: `'(512) 555-0199'`, `'$1,234.56'`, `'10/09/2026'`, `'Inc'`, US states. Each locale provider mixes language data with country conventions.

Quidam's core has no locale (one pack is included, [pl_PL](../src/LanguagePack/pl_PL/README.md)), and it separates the two:

* **conventions** (formats, currency, calling code, country) live in one [`LocaleInterface`](locale.md) service - defaults are neutral and obviously international: `'2026-10-09'`, `'1,234.50 EUR'`, `'034 062 0174'`, `'Hunter Square 42, 53154 West Raymond'`, `'Capital District'`. A language pack sets them with one `new Locale(...)`
* **language data** (names, words) is overridable constants - a pack subclasses extensions and overrides data only
* names from any pack become valid ASCII emails - `Internet` transliterates (`Łukasz` → `lukasz`); Faker's `en_US` email code doesn't need it, other locales handle it per provider

Why it matters: tests written against US-shaped defaults break or mislead in non-US apps (phone validators, date parsing, price formatting). Neutral defaults look fake on purpose, and one locale definition switches everything at once.

## Data quality

Measured side by side, generator by generator: [Data quality - generators improved over FakerPHP](data-quality.md).

**Valid identifiers.** Both libraries generate cards with a valid Luhn digit, IBANs with a valid checksum, EAN / ISBN with valid check digits and Luhn-valid IMEIs. Quidam adds:

| | Quidam |
|---|---|
| EAN-13 / EAN-8 | real GS1 country prefixes |
| ISBN | valid registration groups |
| IBAN | 89 countries with registry structure; unknown country throws (no silent random); locale country by default |
| IMEI | real reporting body prefix |
| VIN | real manufacturer prefix, valid check digit |
| Bitcoin addresses | legacy, P2SH (Base58Check) and SegWit (Bech32) with valid checksums |
| Ethereum addresses | EIP-55 mixed-case checksum |
| UUID | `uuid4()` from 128 random bits (Faker: MD5 of two `mt_rand()` numbers, ~62 bits), plus `uuid7()`, ULID, `uuid3/5()`, nanoid, snowflake |
| calculators | check digit services with `isValid()` - replace for invalid values, use for validation |

**Safe and current values.**

* `email()` always uses reserved `example.com/org/net` - it can't reach a real inbox. Faker's `email()` uses generated domains (`smith.com`) or real free providers (`gmail.com`); its safe variant is `safeEmail()`
* user agents follow current formats (Chrome User-Agent Reduction, browser versions estimated from the clock); Faker's `chrome()` gives Chrome 36-40 (2014) with platforms like `Windows 98` / `Windows CE`
* blood types by real frequency, card brands by popularity

**Values that belong together.**

* `Profile::person()` - name and title match gender, email is the user name, age matches the birth date, mobile phone and address of the locale
* [Filler](addons/filler.md) add-on - DTOs filled from one profile: name, email, phone and address fields belong together
* `Internet::email('Anna', 'Kowalski')` - email for names you already have
* `Address::addressData()` - street, city, region, county and postcode of one record
* `PhoneNumber::internationalPhoneNumber()` / `e164PhoneNumber()` built from a national number of the locale country

**Realistic distributions.** `random()->weightedElement(['new' => 70, 'paid' => 25])` and `Number::gaussian()` / `gaussianInt()` (bell curve with limits) - they replace Faker's `Biased`, see [No Biased](data-quality.md#no-biased---distributions-done-differently).

## Only in Quidam

| Feature | |
|---|---|
| interfaces for everything | extensions, locale, randomizers, calculators, transliterator, clock, strategy, container |
| `LocaleInterface` | country conventions in one definition, neutral defaults |
| `fork()` | stable records with one seed |
| `many()`, `sequence()` | lists and counters (`user1@...`, `INV/01001`) |
| `createSecure()` / `secure()` | cryptographically secure generator, replaceable source |
| generator-wide strategies | DummyGenerator-compatible `StrategyInterface`, `CompositeStrategy` |
| [Filler](addons/filler.md) add-on | DTOs / arrays filled by property names and types |
| [EdgeCases](addons/edge-cases.md) add-on | strings that break code - unicode, RTL, invisible characters, injection probes |
| [RichText](addons/rich-text.md) add-on | lorem as HTML (`randomHtml()`) **and** Markdown from the same blocks |
| [Text](addons/text.md) add-on | Markov text with a corpus passed at runtime (`withCorpus()`), model shared per process |
| [Command line](cli.md) | `vendor/bin/quidam internet.email -n 5 --unique` - text, JSON, CSV |
| [SeedSnapshot](quidam.md#the-same-guard-for-your-extensions-and-packs) | seed snapshot tests for packs and extensions |
| date ranges | `dateTimeRange()` (start <= end, max duration), `dateTimesBetween()` (sorted series) |
| `gaussian()` | normal distribution with limits |
| `Profile` | coherent person record |
| commerce, vehicles, system | products, SKUs, promo codes; VIN, makes / models, plates; cron, log levels, env names |
| finance | bank accounts, masked cards, crypto addresses, prices in minor units by ISO 4217 decimals |
| internet | CIDR ranges, hostnames, hashtags, social handles, JWT-shaped tokens, API keys |
| phone | mobile and international formats consistent with the locale |
| version | semver constraints, git branch names, conventional commit messages |

## Performance

Measured on the same machine ([details](performance.md)), Quidam on the built-in container:

* startup 3.1 ms vs 5.6 ms, a new instance 1.2 µs vs 20 µs
* names, emails, addresses and text 2-9× faster; an 8-field user record 21 µs vs 67 µs
* 1000 unique emails 5.1 µs per email vs 19 µs

## What Faker still has

**More locales - its one real advantage.** Faker ships 75 locales with local names, addresses, phone formats and national ids (`ssn`, `vat`...); Quidam ships one complete pack, [pl_PL](../src/LanguagePack/pl_PL/README.md) (PESEL, NIP, REGON, toll-free numbers, real places and postcodes). A Faker locale is mostly data, and it ports to Quidam as `protected const` arrays in subclasses of the core extensions - pl_PL started from Faker's pl_PL, then was checked, fixed and extended. See [Override data - language packs](extending.md#override-data---language-packs). Values Faker builds from `{{ }}` format strings become plain methods; US-only values (toll-free numbers, ABA routing numbers) belong in a US pack.

Faker's other extras aren't reasons to stay:

* **images** - `image()` / `imageUrl()` are deprecated since Faker 1.20 and removed in Faker 2
* **templates, `realText()`, `randomHtml()`** - Quidam has them too, as opt-in add-ons: Template, Text (`realText()`) and RichText (`html()`, plus `markdown()`)
* **`file()` copying real files, `Biased`, `emoji()`** - niche helpers, a few lines in an [own extension](quidam.md#own-extensions) if you need them
* **older PHP** - Faker runs on PHP 7.4+, Quidam requires PHP 8.4+
* **ecosystem** - Faker has years of use and is built into Laravel factories, Foundry and Alice; Quidam works next to them, see [Integrations](integrations.md)

Note on newer PHP: the tested Faker version emits deprecation notices on PHP 8.4+ (an implicitly nullable parameter in `Faker\Generator`) and, when seeded, on PHP 8.3+ (`MT_RAND_PHP`). Newer Faker releases may have fixed some of them.
