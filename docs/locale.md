# Locale

The core has no locale - its defaults are neutral, and languages come as packs. One complete pack is included as an example: [pl_PL](../src/LanguagePack/pl_PL/README.md). Small ones for fun as well: [akk_IQ](../src/DeadLanguagePack/akk_IQ/README.md) (Akkadian), [la_VA](../src/DeadLanguagePack/la_VA/README.md) (classical Latin), [grc_GR](../src/DeadLanguagePack/grc_GR/README.md) (Ancient Greek), [non_IS](../src/DeadLanguagePack/non_IS/README.md) (Old Norse), [got_IT](../src/DeadLanguagePack/got_IT/README.md) (Gothic), [gmh_DE](../src/DeadLanguagePack/gmh_DE/README.md) (Middle High German), [fro_FR](../src/DeadLanguagePack/fro_FR/README.md) (Old French), [ang_GB](../src/DeadLanguagePack/ang_GB/README.md) (Old English), [enm_GB](../src/DeadLanguagePack/enm_GB/README.md) (Middle English), [en_XS](../src/DeadLanguagePack/en_XS/README.md) (Shakespearean English) and [tlh_QO](../src/DeadLanguagePack/tlh_QO/README.md) (Klingon) - see [Dead languages](dead-languages.md). Data is split in two:

| | What | Where | Default |
|---|---|---|---|
| **language** | names, streets, cities, words, month names, legal forms | `protected const` lists of extensions (`Person::FIRST_NAME_MALE`, `DateTime::MONTHS`...) | English |
| **country conventions** | date / time / price formats, currency, phone calling code, the country of IBANs and timezones | one `LocaleInterface` service shared by all extensions | international sample, no country |

A language pack replaces both - the locale with one definition, the data by subclassing extensions - see [Extending](extending.md).

* [Neutral defaults](#neutral-defaults)
* [LocaleInterface](#localeinterface)
* [Setting a locale](#setting-a-locale)
* [Formats in extensions](#formats-in-extensions)
* [Names in other scripts](#names-in-other-scripts)
* [Not in the core](#not-in-the-core)

## Neutral defaults

Without a pack you get values which look real, but don't belong to any country - and, unlike US-style defaults common in faker libraries, don't pretend to:

| Value | Default | Not |
|---|---|---|
| `DateTime::localDate()`, `localDateTime()`, `longDate()` | `'2026-10-09'`, `'2026-10-09 14:05'`, `'9 October 2026'` (ISO 8601, 24-hour) | `'10/09/2026 2:05 PM'` |
| `Payment::localPrice()`, `formatPrice()` | `'1,234.50 EUR'` | `'$1,234.50'` |
| `PhoneNumber::phoneNumber()` | `'034 062 0174'` - trunk prefix `0`, area code, number | `'(512) 555-0199'` |
| `PhoneNumber::e164PhoneNumber()`, `internationalPhoneNumber()` | random real country - `'+385 888 228 453'`; `e164PhoneNumber('DE')` for a given one | |
| `Address::address()` | `'Hunter Square 42, 53154 West Raymond'` - street, number, postcode, city | `'587 Whitaker Avenue 81-319 Priceport'` |
| `Address::state()`, `county()` | `'Capital District'`, `'Fletcher District'` | `'California'`, `'Harris County'` |
| `Company::company()` | `'Young-Patterson Group'` | `'Smith Inc'` / `'LLC'` |
| `Vehicle::licensePlate()` | `'IM 8822X'` | `'ABC-1234'` |
| `Payment::iban()`, `DateTime::timezone()` without arguments | random country | |

**Which methods take a country.** Local formats - prices, dates, national phone numbers - follow the locale only: one country per generator. International identifiers take an optional country besides, because tests often need foreign ones whatever the locale (a Polish shop receiving a German IBAN or phone number): `Payment::iban('DE')`, `PhoneNumber::e164PhoneNumber('DE')` and `DateTime::timezone('DE')`. Without it they follow the locale.

US-only generators (toll free numbers, ABA routing numbers) are not in the core - they belong to an en_US pack.

Words stay English - names, streets, products, month names - as a sample language most developers read.

## LocaleInterface

`Quidam\Definitions\Locale\LocaleInterface`, default `Quidam\Core\Locale\Locale`:

| Method | Default | Used by |
|---|---|---|
| `countryCode(): ?string` | `null` - no country | `Payment::iban()` default country (if it uses IBAN), `DateTime::timezone()` default country |
| `languageCode(): string` | `'en'` | language of generated words - for packs and your extensions (pl_PL: the `language` of `citizen()`); core extensions don't read it |
| `callingCode(): ?string` | `null` - random country | `PhoneNumber::internationalPhoneNumber()`, `e164PhoneNumber()` |
| `trunkPrefix(): string` | `'0'` | removed from national numbers written with the calling code (`020 7946 0000` → `+44 20 7946 0000`) |
| `currencyCode(): string` | `'EUR'` | `Payment::localCurrency()`, `localPrice()`, `formatPrice()`, `priceInMinorUnits()` default currency |
| `currencySymbol(): string` | `'€'` | `localCurrency()`, `{symbol}` in price pattern |
| `decimalSeparator(): string`, `thousandsSeparator(): string` | `'.'`, `','` | `formatPrice()`, `localPrice()` |
| `pricePattern(): string` | `"{amount}\u{00A0}{code}"` | `formatPrice()` - placeholders `{amount}`, `{symbol}`, `{code}` |
| `dateFormat(): string` | `'Y-m-d'` | `DateTime::localDate()`, `localDateTime()` - `DateTimeInterface::format()` pattern |
| `timeFormat(): string` | `'H:i'` | `DateTime::localDateTime()` |
| `longDateFormat(): string` | `'{day} {month} {year}'` | `DateTime::longDate()` - `{month}` from `DateTime::MONTHS_IN_DATES` |

`Locale` validates what it can: country code must be ISO 3166-1 alpha-2 uppercase, calling code 1-3 digits without `+`, currency ISO 4217 - otherwise `InvalidArgumentException`.

## Setting a locale

`Locale` takes every value as a named constructor argument - a locale is one object definition:

```php
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Locale\LocaleInterface;

$pl = $generator->withDefinition(LocaleInterface::class, new Locale(
    countryCode: 'PL',
    languageCode: 'pl',
    callingCode: '48',
    trunkPrefix: '',
    currencyCode: 'PLN',
    currencySymbol: 'zł',
    decimalSeparator: ',',
    thousandsSeparator: "\u{00A0}",
    pricePattern: "{amount}\u{00A0}{symbol}",
    dateFormat: 'd.m.Y',
));

$pl->payment()->formatPrice(1234.5);     // '1 234,50 zł'
$pl->payment()->iban();                  // 'PL91388228453154089397198406' - locale country
$pl->dateTime()->timezone();             // 'Europe/Warsaw'
$pl->dateTime()->localDate();            // '06.11.1980'
```

Values for some common conventions:

| | en_US | en_GB | de_DE | pl_PL |
|---|---|---|---|---|
| `countryCode` / `callingCode` / `trunkPrefix` | `US` / `1` / `''` | `GB` / `44` / `'0'` | `DE` / `49` / `'0'` | `PL` / `48` / `''` |
| `currencyCode` / `currencySymbol` | `USD` / `$` | `GBP` / `£` | `EUR` / `€` | `PLN` / `zł` |
| separators (decimal, thousands) | `.` `,` | `.` `,` | `,` `.` | `,` NBSP |
| `pricePattern` | `{symbol}{amount}` | `{symbol}{amount}` | `{amount} {symbol}` | `{amount} {symbol}` |
| `dateFormat` / `timeFormat` | `m/d/Y` / `g:i A` | `d/m/Y` / `H:i` | `d.m.Y` / `H:i` | `d.m.Y` / `H:i` |
| `longDateFormat` | `{month} {day}, {year}` | `{day} {month} {year}` | `{day}. {month} {year}` | `{day} {month} {year}` |

The locale is a service like any other - implement `LocaleInterface` yourself when values come from elsewhere (i.e. `NumberFormatter` / ICU data, or your application's settings).

## Formats in extensions

Formats which mix conventions with language data stay in extensions, as overridable templates and masks:

| Extension | Constant | Default |
|---|---|---|
| `Address` | `ADDRESS_FORMAT` | `'{street}, {postcode} {city}'` |
| `Address` | `STREET_ADDRESS_FORMAT` | `'{street} {buildingNumber}'` |
| `Address` | `COUNTY_FORMAT` | `'{name} District'` |
| `Address` | `BUILDING_NUMBER`, `POSTCODE`, `SECONDARY_ADDRESS`, `STATES` | 1-3 digits, `#####` / `####`, `Apt.` / `Suite`..., generic regions |
| `Person` | `NAME_FORMAT` | `'{firstName} {lastName}'` |
| `Company` | `COMPANY_FORMATS`, `SUFFIX` | `'{lastName} {suffix}'`..., `Group` / `Holding`... (legal forms belong to packs) |
| `PhoneNumber` | `FORMATS`, `MOBILE_FORMATS` | `'0%# ### ####'`..., `'07%## ######'`... |
| `Vehicle` | `LICENSE_PLATE_FORMATS` | `'?? #####'`... |
| `DateTime` | `MONTHS`, `MONTHS_IN_DATES`, `DAYS` | English names |

See each extension's page ([Address](extensions/address.md#formats), [PhoneNumber](extensions/phone-number.md), [Payment](extensions/payment.md#local-currency-and-prices), [DateTime](extensions/date-time.md)) for examples.

## Names in other scripts

Language packs bring names like `Łukasz Wójcik`, `Ђорђе` or `Θεσσαλονίκη`. `Internet` turns names into ASCII with `TransliteratorInterface` before they become user names, emails or domains - so a pack gets valid emails without overriding anything:

```php
$pl->internet()->email('Łukasz', 'Wójcik');   // i.e. 'wojcik.lukasz@example.com', 'lwojcik84@example.com'
```

Built-in table: Latin with diacritics, Greek, Cyrillic, Georgian, Armenian - see [Transliterator](extensions/transliterator.md), also for language-specific rules (Ukrainian `Київ` → `Kyiv`).

## Not in the core

* **language data** - names, streets and words of other languages; packs subclass extensions
* **national identifiers** - SSN, PESEL, NIP, VAT ids; packs add their own extensions
* **US-only values** - toll free numbers, ABA routing numbers - an en_US pack
* **real places** - `Address::cityData()` returns fictional records where region and code match; packs can return real ones where city, region, postcode and coordinates match
