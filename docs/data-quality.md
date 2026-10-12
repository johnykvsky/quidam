# Data quality - generators improved over FakerPHP

Many FakerPHP generators return values that look right but aren't: barcodes with prefixes nobody assigned, MAC addresses no network card can have, passwords your own validator rejects, text cut in the middle of a sentence, browsers from 2014. Tests built on them pass against data that real validators, parsers and users would reject. Quidam keeps the generators and fixes the values. This page is about the generated data only - for API and architecture see [Quidam vs FakerPHP](comparison.md), for generators Faker doesn't have see [Only in Quidam](comparison.md#only-in-quidam).

* [Measured](#measured)
* [Improved generators](#improved-generators) - [Text](#text), [Person](#person), [Internet](#internet), [Strings](#strings), [Number](#number), [Uuid](#uuid), [Payment](#payment), [Barcode](#barcode), [PhoneNumber](#phonenumber), [Address and Language](#address-and-language), [Color](#color), [File](#file), [UserAgent](#useragent), [Blood](#blood), [RichText](#richtext)
* [No Biased - distributions done differently](#no-biased---distributions-done-differently)

## Measured

Share of wrong, unsafe or unrealistic values in 10 000 values per generator, FakerPHP main after v1.23.0 (the version used in [Performance](performance.md)) with default `en_US`, measured 2026-10-12. Reproduce with `php bench/data-quality.php` - it checks values with independent rules (PHP's `filter_var()`, IEEE MAC bits, ISO 3166 codes, GS1 and ISBN tables), not with Quidam's generators.

| | FakerPHP | Quidam |
|---|---|---|
| `email()` outside reserved `example.*` domains | 100.0% | 0.0% |
| `ipv4()` private, shared or reserved | 6.9% | 0.0% |
| `ipv6()` not global unicast | 1.0% | 0.0% |
| `macAddress()` multicast - not a device address | 49.3% | 0.0% |
| `macAddress()` universally administered - may be a real device | 50.4% | 0.0% |
| `swiftBicNumber()` country not in ISO 3166 | 63.7% | 0.0% |
| `swiftBicNumber()` test BIC (location code `?0`) | 2.7% | 0.0% |
| `creditCardNumber()` 13 digits ("Visa Retired") | 6.7% | 0.0% |
| `ean13()` prefix not assigned by GS1 | 52.3% | 0.0% |
| `isbn13()` unknown registration group | 46.6% | 0.0% |
| `imei()` unknown reporting body | 95.8% | 0.0% |
| `randomFloat(1, 0, 0.98)` above max | 3.0% | 0.0% |
| `randomFloat(2, 0.001, 0.009)` outside range | 100.0% | throws |
| `password()` without lowercase, uppercase, digit and special character | 31.4% | 0.0% |
| `password()` shorter than 8 characters | 13.0% | 0.0% |
| `name()` with a title or suffix (`Mrs.`, `DVM`, `III`) | 41.6% | 0.0% |
| `realText()` with unpaired brackets or quotes | 17.0% | 0.0% |
| `latitude()` beyond ±60° (Earth surface: 13.4%) | 33.1% | 13.5% |

* Chrome versions in `chrome()`: FakerPHP 36-40 (2014), Quidam current ones (146-154 in October 2026)
* FakerPHP `userAgent()` on Windows 95 / 98 / CE / NT 4 / 2000 / XP: 34%
* `bloodGroup()`: FakerPHP every group ~12.5%; Quidam O+ 38%, A+ 34%, B+ 9%, O- 7%, A- 6%, AB+ 3%, B- 2%, AB- 1%

## Improved generators

### Text

`realText()` - a Markov chain over a book in both libraries, built differently ([Text add-on](addons/text.md)):

* **starts at a sentence start** - Faker starts at any capitalized word, so texts open mid-sentence (`'Rabbit asked. …'`, `'Caterpillar. …'`); Quidam starts where a sentence of the book starts
* **ends at a sentence end** - Faker cuts at the last word that fits and adds a dot (`'… the patriotic archbishop of.'`); Quidam cuts at the last sentence end within the limit, and only falls back to a word when no sentence end keeps the minimum length
* **pairs brackets and quotes** - 17% of Faker's texts have an unpaired `(` or `“`; Quidam removes the unpaired ones
* **explicit length** - `realText($min, $max)`; Faker's `realText($max)` means 80-100% of `$max`
* **built once per process** - Faker builds the model in every generator instance (6.6 ms for each new `Faker\Generator`); Quidam's model is shared by all instances, so a test suite with a generator per test builds it once (0.08 ms per new generator after that)
* **any corpus at runtime** - `withCorpus($text)` or a subclass with its own `CORPUS`; Faker's text comes from the locale provider class

### Person

* **`name()` is a name** - 41.6% of Faker's `en_US` `name()` values carry a title or suffix (`'Mrs. Elisa Carroll DVM'`, `'Antonetta Effertz MD'`), which breaks splitting into first and last name and looks wrong in "full name" fields. Quidam's `name()` is first and last name of one gender; `title()` and `suffix()` are separate

### Internet

* **emails can't reach anyone** - Faker's `email()` uses real free providers (`gmail.com`, `hotmail.com`) or generated domains (`smith.com`) that may exist, so a test that really sends mail can reach real people. Quidam's `email()` always uses the reserved `example.com` / `example.org` / `example.net` (RFC 2606); realistic-looking addresses are explicit: `freeEmail()`, `companyEmail()`
* **passwords pass password rules** - Faker's `password()` is 6-20 random characters: 31.4% lack a lowercase letter, uppercase letter, digit or special character, 13% are shorter than 8. Quidam's is 8-20 characters with all four types
* **IP addresses are public** - 6.9% of Faker's `ipv4()` values are private (`10.x`, `192.168.x`), loopback, carrier-grade NAT (`100.64.0.0/10`), multicast or reserved, so "is this a public client IP" tests get flaky. Quidam's `ipv4()` and `ipv6()` are public unicast only, `localIpv4()` gives a private one
* **MAC addresses are valid device addresses** - Faker joins six random bytes: half have the multicast bit set (no network card can have them), half are universally administered (the prefix may belong to a real vendor). Quidam's are unicast and locally administered - second hex digit `2`, `6`, `A` or `E`

### Strings

* **`regexify()` output always matches the pattern** - Faker's regexify silently produces non-matching values for syntax it doesn't know: `[^aeiou]{5}` gives vowels (`'uueua'`), `(?:ab){2}` gives `':ab:ab'`, `[[:alpha:]]{3}` gives `'[]]]'`. Quidam's parser supports negated classes, non-capturing groups, `\x{…}` and Unicode ranges, and throws for what it can't generate (lookarounds, backreferences, `\p{…}`, POSIX classes) - it never returns a value that doesn't match

### Number

* **`randomFloat()` stays in range** - Faker scales a random number and rounds afterwards, so rounding steps outside the range: `randomFloat(1, 0, 0.98)` returns `1.0` in 3% of calls, `randomFloat(2, 0.001, 0.009)` - where no 2-decimal number exists - always returns `0.0` or `0.01`. Quidam picks from the integers of the scaled range: always within `[$min, $max]`, every value equally likely, an impossible request throws
* **`randomNumber()` has 18 digits** - Faker stops at 9 (`mt_getrandmax()`) and throws for more
* **bell curves and weights** - `gaussian()`, `gaussianInt()` and `random()->weightedElement()`, see [No Biased](#no-biased---distributions-done-differently)

### Uuid

* **full randomness** - Faker's `uuid()` is an MD5 hash of two 31-bit `mt_rand()` numbers: ~62 bits of randomness behind a 122-bit format. Quidam's `uuid4()` takes its random bits from the generator's engine (cryptographically secure with `secure()`); `uuid7()`, ULID, `uuid3()` / `uuid5()`, nanoid and snowflake IDs are there too

### Payment

* **BIC codes have real countries** - Faker's `swiftBicNumber()` takes two random letters as the country (63.7% aren't one) and 2.7% of its values are test BICs. Quidam uses countries with banking registries, never test BICs, and branch codes never start with `X` (reserved for `XXX`, the primary office)
* **IBANs don't silently break** - Faker's `iban('XX')` returns `XX85593613894617007238968469` for a country it doesn't know; Quidam throws, and without a country it uses the locale's, with the registry structure of 89 countries
* **card numbers are current** - Faker's `creditCardType()` includes "Visa Retired": 13-digit numbers no longer issued, rejected by modern validators. Quidam has current brands, picked by popularity
* **currencies are in circulation** - Faker's list includes `SLL` (replaced by `SLE` in 2022) and `CUC` (abolished in 2021); Quidam picks from the 154 circulating ISO 4217 currencies, without precious metals, testing and fund codes

### Barcode

* **assigned prefixes** - Faker's `ean13()` is twelve random digits and a check digit: 52.3% start with a prefix GS1 never assigned. Its `isbn13()` has a valid `978` / `979` prefix and check digit, but 46.6% use a registration group that doesn't exist. Quidam uses real GS1 prefixes (without restricted, in-store and coupon ranges) and real ISBN registration groups, so values pass validators that check more than the check digit

### PhoneNumber

* **IMEIs with real reporting bodies** - 95.8% of Faker's `imei()` values start with digits that aren't a reporting body; Quidam uses the real ones (`35`, `86`, `01`, `99`), with a valid Luhn digit

### Address and Language

* **current countries** - Faker's `en_US` `country()` still has Swaziland (Eswatini since 2018), Macedonia (North Macedonia since 2019) and Netherlands Antilles (dissolved in 2010). Quidam's 249 ISO 3166-1 countries come from `iso-codes` data, with matching alpha-2, alpha-3 and name in `countryData()`
* **living languages, current locales** - Faker's `languageCode()` includes Latin, Sanskrit, Avestan, Old Church Slavonic, Pali, Esperanto, Volapük, Ido, Interlingua and Interlingue; its `locale()` includes `sr_YU`, `sh_YU`, `sr_CS`, `sh_CS` (Yugoslavia, Serbia and Montenegro). Quidam has 174 living ISO 639-1 languages and current locales only
* **coordinates spread over the Earth, not over degrees** - Faker's `latitude()` is uniform in degrees, so points crowd at the poles: a third lie beyond ±60°, where 13.4% of the surface is. Quidam's are uniform over the surface (uniform in the sine of latitude)
* **parts that belong together** - `cityData()` / `addressData()` return city, region, county and postcode of one record; separate `city()` and `postcode()` calls - Faker's only option - don't match each other

### Color

* **all CSS colors** - Faker's `colorName()` has 141 names, without `RebeccaPurple` and six of the seven `Grey` spellings (`Grey`, `LightGrey`, `SlateGrey`...); Quidam has all 148 CSS Color Module Level 4 named colors

### File

* **common types, matching names** - Faker's `mimeType()` picks from 461 types, mostly ones you'll never upload (`application/vnd.ezpix-album`, `application/vnd.ibm.secure-container`). Quidam has 56 commonly used types, `fileName()` with a name that fits the type (`'IMG_0996.jpg'`, `'invoice_2024.pdf'`) and `mimeTypeForExtension()` - name, type and size of one attachment agree

### UserAgent

* **browsers from this decade** - Faker's `chrome()` gives Chrome 36-40 (2014) and 34% of its `userAgent()` values run on Windows 95, 98, CE, NT 4, 2000 or XP. Quidam follows current formats (Chrome's User-Agent Reduction) with versions estimated from the clock, so user-agent parsers, analytics and bot detection are tested against what they see in production

### Blood

* **real frequencies** - Faker picks each of the 8 groups with the same 12.5% chance; in reality O+ is about 38% and AB- about 1%, and Quidam's `bloodGroup()` follows that

### RichText

* **content, not markup noise** - Faker's `randomHtml()` nests random elements (forms with inputs, `<div>`s with numeric ids, tables) - valid HTML, but nothing like what an editor or CMS stores. The [RichText add-on](addons/rich-text.md) builds documents from real content blocks: paragraphs with bold / italic / links / inline code (never across a sentence end), headings, lists, quotes, code blocks, tables and figures - as HTML, a full HTML5 document, or Markdown from the same blocks

## No Biased - distributions done differently

Faker's `Biased` provider has one method, `biasedNumberBetween($min, $max, $function)`: an integer in a range, skewed by a density function given by name (`'sqrt'` by default - more high values - or `linearLow`, `linearHigh`). It draws random pairs until one is accepted, from the global `mt_rand()`.

Quidam has no `Biased`, because what it's used for is covered more directly - on the generator's own randomizer, so seeds and forks apply:

```php
// values around a typical one - a bell curve with limits: ages, heights, durations, prices
$generator->number()->gaussianInt(38, 12, 18, 90);         // most ages 26-50, never below 18 or above 90
$generator->number()->gaussian(170, 10, 140, 210, 1);      // 174.3

// any shape you can write as weights - here linearLow: 1 most often (18%), 10 least (2%)
$generator->random()->weightedElement(array_combine(range(1, 10), range(10, 1)));

// categories directly - something Biased can't do
$generator->random()->weightedElement(['new' => 70, 'paid' => 25, 'refunded' => 5]);
```

A continuous curve like Faker's default `'sqrt'` is one line in an [own extension](quidam.md#own-extensions) - inverse transform sampling from the randomizer, no rejection loop:

```php
// same distribution as Faker's biasedNumberBetween($min, $max, 'sqrt')
min($max, $min + (int) floor(($max - $min + 1) * $randomizer->getFloat(0, 1) ** (2 / 3)));
```
