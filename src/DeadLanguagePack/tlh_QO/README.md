# Klingon language pack (tlh_QO)

An unofficial fan pack for fun, after [Latin](../la_VA/README.md) and [Ancient Greek](../grc_GR/README.md). Warriors are named by their fathers, cities lie on the worlds of the Empire, house numbers are Klingon, prices are in darseks and dates are stardates. `tlh` is the ISO 639 code of Klingon; `QO` is a user-assigned ISO country code (for Qo'noS) that no real country will ever get.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\tlh_QO\TlhQoProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new TlhQoProviderPack());

$generator->person()->name(Gender::Male);      // "Makh, son of M'Rokh"
$generator->person()->name(Gender::Female);    // "B'Rela, daughter of Rakhar"
$generator->person()->title(Gender::Male);     // 'Councillor'
$generator->address()->address();              // "Path of Honor wa'maH, Ghorvak Station" - house number 10
$generator->address()->secondaryAddress();     // 'Barracks chorgh'
$generator->address()->cityData('QO');         // ['city' => 'Krennok', 'state' => "Qo'noS", 'county' => 'House of Qaxor', ...]
$generator->company()->company();              // 'House of Ghorek Armaments', "Akhor's Trophy Hall"
$generator->company()->jobTitle();             // 'Chief Engineer'
$generator->company()->catchPhrase();          // 'Our blades never rust'
$generator->dateTime()->longDate();            // 'Stardate 2026.285' - 12 October 2026
$generator->payment()->localPrice(10, 5000);   // '714 darsek'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l tlh_QO person.name -n 3 -s 2026
Ghessa, daughter of Kruvok
Sorgha, daughter of Akhor
Kargh, son of Lorgath
```

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | no Earth country - phone numbers, IBANs and timezones of random countries; darseks as `1,250 darsek` | - |
| `Person` | 28 men's and 21 women's names, ranks and offices as titles (`General`, `Dahar Master`) | `lastName()` - "son of Torvek" / "daughter of Torvek"; names written "Kargh, son of Torvek" |
| `Address` | 21 cities on 10 worlds (`Qo'noS`, `Boreth`, `Rura Penthe`...), 12 streets, Great Houses as counties | building numbers and quarters in Klingon; `cityData('QO')` |
| `DateTime` | - | `longDate()` - stardates |
| `Company` | Houses and shops (`Bloodwine Cellar`, `Gagh Bar`, `Bat'leth Forge`), warrior trades, battle cries as catch phrases | `company()` - "House of Kargh Shipyards" |
| `Payment` | the darsek, under `XXX` (ISO 4217 "no currency"), whole coins | - |

## Klingon numbers

House numbers are written in Klingon: digits are `wa'` (1), `cha'` (2), `wej` (3), `loS` (4), `vagh` (5), `jav` (6), `Soch` (7), `chorgh` (8), `Hut` (9); a digit with `maH` is tens, with `vatlh` hundreds. So 10 is `wa'maH`, 23 `cha'maH wej`, 123 `wa'vatlh cha'maH wej`.

## Stardates

The Empire's own calendar isn't known, so `longDate()` gives the stardate starships log in: the year and the day of the year - `Stardate 2026.285` is 12 October 2026, `Stardate 2026.1` is New Year's Day. Month and day names stay English.

## About the data

Star Trek and Klingon are trademarks of Paramount; this is an unofficial fan pack, with no connection to them. To keep it that way, it uses only a handful of well-known words (the numbers, `Qapla'`, `bat'leth`, `gagh`, `targ`) and a few famous place names. Personal names, most cities and streets, and the catch phrases were made up for the pack - no vocabulary or text is taken from the Klingon dictionary or other books.
