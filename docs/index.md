# QuidamPHP documentation

QuidamPHP (`Quidam` in code) is a dummy / fake data generator for PHP 8.4+ with the architecture of [DummyGenerator](https://github.com/johnykvsky/dummygenerator) - every part behind an interface in a container, language providers, strategies - without its magic: no `__call()`, no string templates, no PHP-DI dependency. Defaults are English words with neutral, country-less formats; country conventions live in one [locale](locale.md) service.

Coming from Faker or DummyGenerator? See [Quidam vs FakerPHP](comparison.md) and the [Migration guide](migration.md). Using Laravel, Symfony or Slim? See [Integrations](integrations.md). Speed compared with Faker and DummyGenerator: [Performance](performance.md).

```php
use Quidam\Quidam;

$generator = Quidam::create();

$generator->person()->name();          // 'Brenda Patterson'
$generator->internet()->email();       // 'brenda_patterson@example.org'
$generator->dateTime()->dateOfBirth(); // DateTimeImmutable 1970-07-18
```

Example values in this documentation show formats - your values will differ. Values marked as generated with a seed were generated with `Quidam::create(seed: 2026)` and clock frozen at `2026-10-08 12:00 Europe/Warsaw`.

## Core

* [Quidam](quidam.md) - creating a generator, accessors and `ext()`, seed, clock, randomizers, secure randomness, forks, `many()`, `sequence()`, own extensions, `withDefinition()`, what can be replaced, exceptions, architecture
* [Locale](locale.md) - neutral defaults, `LocaleInterface` (currency, formats, calling code, country), names in other scripts
* [Data quality](data-quality.md) - generators improved over FakerPHP, measured: text, names, passwords, emails, IPs, MAC addresses, `regexify()`, floats, UUIDs, BICs, cards, barcodes, IMEIs, countries and languages, colors, files, user agents, rich text; why there's no `Biased`
* [Extending](extending.md) - language packs: overriding data, provider and definition packs, calculators, own replaceable extensions
* [Polish language pack](../src/LanguagePack/pl_PL/README.md) - pl_PL included with Quidam: real places, banks, identifiers (PESEL, NIP...) - and a template for other languages
* [Strategies](strategies.md) - unique, valid and optional values, mixing them, generator-wide strategies, own strategies
* [Containers](containers.md) - the built-in container, writing an adapter (PHP-DI example)
* [Add-ons](#add-ons) - Template, Filler, Text, RichText, EdgeCases; [command line](cli.md)

## Extensions

Every extension has a typed accessor - `$generator->person()`, `$generator->dateTime()` - a shortcut of `$generator->ext(XExtensionInterface::class)`. Interfaces are in `Quidam\Definitions\Extension`, default implementations in `Quidam\Core`.

| Extension | Description |
|---|---|
| [Address](extensions/address.md) | Cities, streets, postcodes, regions, address templates, countries with ISO codes, coordinates |
| [Barcode](extensions/barcode.md) | EAN-13, EAN-8, UPC-A, ISBN-13, ISBN-10, ISMN with valid check digits |
| [Blood](extensions/blood.md) | Blood type and Rh factor with realistic frequency |
| [Color](extensions/color.md) | Hex, RGB, HSL colors, CSS color names |
| [Commerce](extensions/commerce.md) | Product names, departments, SKUs, promotion codes |
| [Company](extensions/company.md) | Company names, job titles, industries, catch phrases |
| [DateTime](extensions/date-time.md) | Dates between, past, future, ranges, sorted series, date of birth, timezones, local formats |
| [Enum](extensions/enum.md) | Random case or value of PHP enum |
| [File](extensions/file.md) | MIME types, extensions, file names and paths, file sizes, CSV / JSON contents |
| [Hash](extensions/hash.md) | MD5 / SHA formats, Base64 tokens, JWT-shaped tokens, API keys |
| [Internet](extensions/internet.md) | Emails, usernames, passwords, domains, host names, URLs, IP / CIDR and MAC addresses, HTTP, hashtags, social handles |
| [Language](extensions/language.md) | Languages with ISO 639-1 codes, locales |
| [Lorem](extensions/lorem.md) | Lorem ipsum words, sentences, paragraphs, text with length limit |
| [Number](extensions/number.md) | Integers, digits, floats, normal distribution (bell curve), booleans |
| [Payment](extensions/payment.md) | Credit cards, IBAN, SWIFT / BIC, bank accounts, crypto addresses, currencies, local prices, prices in minor units |
| [Person](extensions/person.md) | Names, middle names, titles, suffixes, gender, initials |
| [PhoneNumber](extensions/phone-number.md) | Landline and mobile numbers, international and E.164 formats, IMEI |
| [Profile](extensions/profile.md) | Coherent records - person with matching gender, name, email and age |
| [Strings](extensions/strings.md) | Random strings from pool, `#` / `?` / `*` masks, strings matching regex |
| [System](extensions/system.md) | Cron expressions, log levels, environment variable names |
| [UserAgent](extensions/user-agent.md) | Browser user agents in current formats, bots |
| [Uuid](extensions/uuid.md) | UUID v4, monotonic UUID v7, ULID, name-based UUID v3 / v5, Nano ID, snowflake ids |
| [Vehicle](extensions/vehicle.md) | Manufacturers and models, VIN with valid check digit, licence plates |
| [Version](extensions/version.md) | Semantic versions and constraints, git commit hashes, branch names, commit messages |

Services used by extensions, available the same way:

| Service | Description |
|---|---|
| [Random](extensions/random.md) - `$generator->random()` | Randomizer: ints, floats, random / weighted elements, shuffle |
| [Transliterator](extensions/transliterator.md) | Text to ASCII and slugs (`Łukasz Żółć` → `Lukasz Zolc`) - used for emails from non-ASCII names |
| [Locale](locale.md) - `LocaleInterface` | Country conventions: currency, price / date formats, calling code, country |
| [Calculators](extending.md#calculators) | Luhn, EAN, ISBN-10, IBAN check digits - `checkDigit()` / `isValid()` |

## Add-ons

Opt-in classes beyond the core extensions - registered under their interfaces (`Quidam\Definitions\Addon`), but nothing is loaded until you ask for one with `$generator->ext(...)`.

| Add-on | Description |
|---|---|
| [Template](addons/template.md) - `TemplateInterface` | Replaces `{{ person.firstName }}` tokens in text, for formats stored as data; `evaluate()` for one expression |
| [Filler](addons/filler.md) - `FillerInterface` | Objects and arrays filled with data guessed from property names and types - person, phone and address fields of one profile |
| [Text](addons/text.md) - `TextInterface` | Realistic text generated from a book (Markov chain), own corpus for other languages |
| [Rich text](addons/rich-text.md) - `RichTextInterface` | Lorem ipsum as HTML (`randomHtml()`) or Markdown - headings, lists, links, tables, code |
| [Edge cases](addons/edge-cases.md) - `EdgeCasesInterface` | Strings that break code - unicode, RTL, invisible characters, injection probes |

## Tools

* [Command line](cli.md) - `vendor/bin/quidam internet.email -n 5 --unique`, templates, JSON / CSV output
* [Seed snapshots](quidam.md#the-same-guard-for-your-extensions-and-packs) - `Quidam\Testing\SeedSnapshot` guards seeded output of your packs and extensions

