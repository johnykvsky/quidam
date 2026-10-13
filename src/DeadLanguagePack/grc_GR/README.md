# Ancient Greek language pack (grc_GR)

A pack for fun, the sibling of [la_VA](../la_VA/README.md). Data is classical Athens and the Greek world: names with father and deme, cities from Massalia to Trapezous, and the Attic calendar dated by Olympiads. Country conventions are those of Greece today. Words are in the usual Latin transliteration ("Sokrates", "Pyanepsion"), so user names and emails need nothing extra; Greek script shows in the numerals.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\grc_GR\GrcGrProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new GrcGrProviderPack());

$generator->person()->name(Gender::Male);      // 'Perikles Isokratous Lamptreus' - Perikles, son of Isokrates, of Lamptrai
$generator->person()->name(Gender::Female);    // 'Arete Meletou' - Arete, daughter of Meletos
$generator->person()->title(Gender::Male);     // 'Strategos'
$generator->address()->address();              // 'Skambonidai δʹ, Eretria' - Greek numerals, no postcode
$generator->address()->secondaryAddress();     // 'ergasterion ηʹ'
$generator->address()->cityData('IO');         // ['city' => 'Miletos', 'state' => 'Ionia', 'county' => 'phyle Akamantis', 'postcode' => '351 43', ...]
$generator->company()->company();              // 'Nikomachos kai Mnesarchos', 'Chabrias kai Hyioi', 'Trapeza Kimonos'
$generator->company()->jobTitle();             // 'Kerameus'
$generator->company()->catchPhrase();          // 'Ktema es aiei'
$generator->dateTime()->longDate();            // 'θʹ Maimakterionos, Ol. χϟϛʹ, etos αʹ'
$generator->dateTime()->dayOfWeek();           // 'hemera Selenes'
$generator->payment()->localPrice(10, 5000);   // '1.392,16 €'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l grc_GR person.name -n 3 -s 2026
Hegeso Nikeratou
Praxagora Hypereidou
Gryllos Kleonos Sphettios
```

## Names

Greeks had no family names. An Athenian citizen was named with his father's name in genitive and his deme (home district): Sokrates Sophroniskou Alopekethen - Sokrates, son of Sophroniskos, of Alopeke. Women were named with their father's name, as on the grave stele of Hegeso Proxenou. `lastName()` returns these parts, so `profile()->person()` and every other user of names gets them; `patronymic()`, `demotic()` and `genitive('Kimon')` (`'Kimonos'`) give them separately.

## Attic calendar

`longDate()` and `monthName()` give the date in the calendar of Athens:

* **months are lunar** - each starts the day after the new moon, when the first crescent shows. The year starts with the first month after the summer solstice, Hekatombaion.
* **13-month years** - twelve lunar months are 354 days, so about every third year has a second Poseideon (`Poseideon deuteros`) to keep up with the sun.
* **years are counted in Olympiads** - four-year periods from the first Olympic games in the summer of 776 BC.

| Date | `longDate()` | Read as |
|---|---|---|
| 12 October 2026 | `αʹ Pyanepsionos, Ol. ψαʹ, etos βʹ` | 1st of Pyanepsion, Olympiad 701, year 2 |
| 16 July 2026 | `αʹ Hekatombaionos, Ol. ψαʹ, etos βʹ` | new year's day |
| 1 January 2026 | `ιβʹ Poseideonos deuterou, Ol. ψαʹ, etos αʹ` | 12th of the second Poseideon |
| 1 August 1 AD | `ιαʹ Metageitnionos, Ol. ρϟεʹ, etos αʹ` | Olympiad 195 begins with year 1 AD |

Months follow the mean new moon, so a date can be a day or two off the real sky - the Athenian archons, who moved the calendar for festivals and politics, were often further off. Days are numbered 1-30 (Athenians counted the last third of a month backwards; this pack doesn't). The locale's long date format doesn't apply. Day names are the planetary week of the Hellenistic age: `hemera Selenes` (Monday)... `hemera Heliou` (Sunday).

Numerals are alphabetic: letters for units, tens and hundreds with the numeral sign - `ζʹ` is 7, `ιβʹ` 12, `ψαʹ` 701, `͵βκϛʹ` 2026. Building numbers use them too.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Greece: `+30`, EUR `1.234,56 €`, `12/10/2026`, `Europe/Athens`, IBANs `GR` | - |
| `Person` | 63 men's names with genitives, 30 women's names, 27 demes, offices as titles (`Strategos`, `Archon`, `Pythia`) | `lastName()` - patronymic and deme; `patronymic()`, `demotic()`, `genitive()` |
| `Address` | 86 cities in 19 regions with coordinates of the site today, 18 streets and quarters of Athens, the ten tribes of Kleisthenes as counties, modern Greek postcodes | building numbers and rooms (`oikia`, `oikema`, `ergasterion`) in Greek numerals; `cityData('IO')` |
| `DateTime` | Attic months, planetary week days | Attic calendar - `monthName()`, `longDate()` |
| `Company` | partnerships (`kai Hyioi`, `kai Koinonoi`), banks and shops (`Trapeza`, `Kerameion`), trades, fields, maxims as catch phrases | business names with the owner's name in genitive |

## Data sources

Names, demes and places are well-known classical ones - many men's names with their real demes (Sokrates Alopekethen, Perikles Cholargeus, Demosthenes Paianieus). Coordinates are of the sites (or modern cities on them), rounded to ~100 m. Like the Latin pack, the data comes from general knowledge of the classical world, not from a curated dataset.
