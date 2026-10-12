# Polish language pack (pl_PL)

A complete language pack shipped with Quidam, and an example of how to write one. The core stays language and country neutral; the pack's code lives in this folder (`Quidam\LanguagePack\pl_PL`), its tests and seed snapshot in [`test/LanguagePack/pl_PL`](../../../test/LanguagePack/pl_PL). Nothing in the core refers to the pack, so it can be moved to its own package without changes.

```php
use Quidam\LanguagePack\pl_PL\PlPlGenerator;

$generator = PlPlGenerator::create(seed: 2026);

$generator->person()->name();                        // 'Kazimiera Wójtowicz' - surnames follow gender
$generator->address()->address();                    // 'os. Toruńska 22, 71-220 Bezrzecze' - real place
$generator->phoneNumber()->mobilePhoneNumber();      // '738 822 845' - real mobile prefixes
$generator->phoneNumber()->phoneNumber();            // '43 188 22 84' - real area codes
$generator->phoneNumber()->e164PhoneNumber();        // '+48788228453'
$generator->phoneNumber()->tollFreePhoneNumber();    // '800-882-284'
$generator->company()->company();                    // 'Zakład Ryba', 'Kozieł-Markiewicz i Syn'
$generator->company()->catchPhrase();                // 'Zaawansowane produkty dla profesjonalistów'
$generator->commerce()->productName();               // 'Smukła wełniana butelka' - adjectives agree with the noun
$generator->internet()->email();                     // 'kpiatek84@example.com' - ASCII
$generator->payment()->iban(formatted: true);        // 'PL43 2728 8223 8453 1540 8939 7198' - real bank code
$generator->payment()->localPrice(10, 500);          // '460,20 zł'
$generator->vehicle()->licensePlate('mazowieckie');  // 'WS 845BA' - real county codes
$generator->nationalId()->pesel();                   // '51071882277'
$generator->nationalId()->landRegistryNumber();      // 'SR1W/00868164/6' - real court code, valid check digit
$generator->dateTime()->longDate();                  // '17 lipca 2001'
$generator->text()->realText(80, 140);               // 'Potem szedł do kuchni i znajdował tam w cienistym kącie...'
```

Values were generated with seed 2026 - yours will differ.

The pack also works on any generator, without `PlPlGenerator`:

```php
use Quidam\Quidam;
use Quidam\LanguagePack\pl_PL\PlPlProviderPack;

$generator = Quidam::create()->withProvider(new PlPlProviderPack());
$generator->person()->name();                        // the same data, accessors typed with core interfaces
```

## Command line

```text
$ vendor/bin/quidam -l pl_PL person.name -n 3 -s 2026
Kazimiera Wójtowicz
Antonina Markiewicz
Teodor Włodarczyk

$ vendor/bin/quidam -l pl_PL -t '{{ nationalId.nip(true) }} | {{ payment.bank }}'
714-103-73-80 | Powszechna Kasa Oszczędności Bank Polski Spółka Akcyjna
```

All Template aliases work with Polish data, the pack's own methods too (`phoneNumber.tollFreePhoneNumber`, `profile.citizen`), plus the `nationalId` alias - the pack registers it with `Template::alias('nationalId')`, so `{{ nationalId.pesel }}` works in your templates as well, next to your own aliases. `--list` with `-l pl_PL` shows the pack's methods.

## Citizen

`profile()->citizen()` - every value belongs to one person and one place: PESEL encodes the birth date and gender, the surname has the form of the gender, county, postcode and coordinates are those of the city:

```php
$generator->profile()->citizen();
// [
//     'gender' => Gender::Female, 'title' => 'inż.', 'firstName' => 'Kazimiera', 'lastName' => 'Wójtowicz',
//     'name' => 'Kazimiera Wójtowicz', 'userName' => 'kazimiera.wojtowicz', 'email' => 'kazimiera.wojtowicz@example.net',
//     'dateOfBirth' => DateTimeImmutable(1954-06-15), 'age' => 72, 'phone' => '883 154 089',
//     'address' => [
//         'street' => 'os. Świdnicka 519', 'city' => 'Postęp', 'state' => 'śląskie', 'stateCode' => 'PL-24',
//         'county' => 'powiat myszkowski', 'postcode' => '42-350', 'latitude' => 50.593066, 'longitude' => 19.274171,
//         'address' => 'os. Świdnicka 519, 42-350 Postęp',
//     ],
//     'pesel' => '54061501783', 'idCardNumber' => 'EWR172463', 'passportNumber' => 'EJ1813196', 'language' => 'pl',
// ]
```

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | `+48`, no trunk prefix, PLN `1 234,56 zł`, `09.10.2026`, `Europe/Warsaw` | - |
| `Person` | 131 male / 123 female first names (no unisex ones - Polish has no established ones), 170 surnames, academic titles | surnames in the form of the gender (Kowalski / Kowalska) |
| `Address` | 249 cities and towns with real postcodes and coordinates (GeoNames), 16 voivodeships, counties, streets, Polish country names | `cityData('PL-14')` - places of one voivodeship |
| `PhoneNumber` | landlines with 49 real area codes, mobiles with 13 real prefixes | `tollFreePhoneNumber()` |
| `DateTime` | month names (also genitive: "9 października"), day names | - |
| `Internet` | Polish free email providers and TLDs | - |
| `Company` | legal forms (`sp. z o.o.`, `S.A.`...), job titles, industries, catch phrases | `companyPrefix()`; names use the base form of surnames |
| `Commerce` | products with grammatical gender, departments, promotion words | adjectives agree with the product |
| `Payment` | 68 banks by sort code | `bank()`, `sortCode()`; IBAN and NRB with real bank code and valid check digit |
| `Vehicle` | voivodeship and county plate codes, special services | `licensePlate($voivodeship, $special)` |
| `Profile` | - | `citizen()` - person with PESEL, ID card and passport numbers, language of the locale |
| `NationalIdInterface` | 343 land registry court codes | PESEL, NIP, REGON (9 and 14 digits), ID card, passport, land registry, doctor's licence (PWZ), KRS, EU VAT |
| `Text` (add-on) | Bruno Schulz, "Sklepy cynamonowe" (1934, public domain) | - |

Every identifier and check digit is verified in the tests by independent validators ([`Validators.php`](../../../test/LanguagePack/pl_PL/Validators.php), written from the specifications, not from the generator code).

## How it's built - writing your own pack

The pack uses only public extension points, so it's a template for other languages:

1. **Locale** - `PlPlProviderPack::locale()` is one `Locale` with the country conventions; `Payment`, `PhoneNumber`, `DateTime` and `Address` read it, so they stay consistent. The pack reads it too - `citizen()` takes the person's language from `languageCode()`.
2. **Data** - most classes only override `protected const` lists of the core classes (`DateTime`, `Internet`). The core reads them with `static::`, so all methods keep working.
3. **Behaviour** - where the language needs it, a method is overridden (`Person::lastName()` for gendered surnames, `Commerce::productName()` for adjective agreement, `Address::cityData()` for real places). `Company` uses the `companyLastName()` hook of the core.
4. **Country-only methods** - added to the subclasses (`tollFreePhoneNumber()`, `bank()`) or as an own extension registered under its own interface (`NationalIdInterface`), so it can be replaced like core extensions.
5. **Provider pack** - `PlPlProviderPack` maps core interfaces to pack classes: `withProvider()` applies the whole pack. It also registers a Template alias of its own extension (`Template::alias('nationalId')`). The CLI finds the pack by naming convention - `-l pl_PL` is `Quidam\LanguagePack\pl_PL\PlPlProviderPack` - so the core doesn't refer to it.
6. **Generator** (optional) - `PlPlGenerator` extends `Quidam`: `create()` applies the pack, and accessors of extensions with own methods return pack classes (`phoneNumber(): PhoneNumber`), so those methods are typed. Replacing such an extension with `withDefinition()` works when the replacement extends the pack class; otherwise the accessor throws and `ext()` still returns it.
7. **Tests** - [`test/LanguagePack/pl_PL`](../../../test/LanguagePack/pl_PL) has behaviour tests and a seed snapshot (`SeededOutputTest.php`, public `Quidam\Testing\SeedSnapshot`), which covers every public method of the pack classes. The [sweep test](../../../test/Sweep/GeneratorSweepTest.php) also runs every pack method found in `src/LanguagePack/`.

Regenerate the snapshot after an intended change:

```shell
UPDATE_SEED_SNAPSHOT=1 vendor-bin/phpunit --no-coverage --filter 'pl_PL'
```

## Extracting to a package

Move this folder to the new package's `src/` and `test/LanguagePack/pl_PL` to its `tests/`, keep the namespaces (or rename them), require `johnykvsky/quidam`, and point the package's PHPUnit at `tests/`. Nothing in the core refers to the pack.

## Data sources

Started from FakerPHP's pl_PL and was checked, fixed and extended: places, postcodes and coordinates from [GeoNames](https://www.geonames.org) (CC BY 4.0), land registry court codes from Dz.U. 2024 poz. 939. Name lists are samples, not statistics.
