# Old Norse language pack (non_IS)

A pack for fun, after [Latin](../la_VA/README.md), [Ancient Greek](../grc_GR/README.md) and [Klingon](../tlh_QO/README.md). Data is the Viking age, in normalized Old Norse spelling (þ, ð, ǫ, the nominative -r): names with patronymics and bynames, runes, towns from Vínland to Miklagarðr, and the Old Icelandic calendar. Country conventions are those of Iceland - its language is the closest living one to Old Norse, and it still names people by their fathers.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\non_IS\NonIsProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new NonIsProviderPack());

$generator->person()->name(Gender::Male);      // 'Hǫskuldr Eiríksson' - Hǫskuldr, son of Eiríkr
$generator->person()->name(Gender::Female);    // 'Jórunn Arnórsdóttir' - Jórunn, daughter of Arnórr
$generator->person()->byname(Gender::Male);    // 'inn svarti' - the Black
$generator->person()->runes();                 // 'ᚦᚢᚱᚢᛚᚠᚱ᛫ᛋᛏᛁᚾᛅᚱᛋᚢᚾ' - Þórólfr Steinarsson
$generator->person()->title(Gender::Male);     // 'konungr' - king
$generator->address()->address();              // 'Kirkjustræti 4, Birka'
$generator->address()->cityData('EN');         // ['city' => 'Lundúnir', 'state' => 'England', 'county' => 'Vaðlaþing', 'postcode' => '587', ...]
$generator->company()->company();              // 'Kaupskip Halls' (Hallr's merchant ship), 'Hákon ok Ingólfr'
$generator->company()->jobTitle();             // 'smiðr' - smith
$generator->company()->catchPhrase();          // 'Gáttir allar áðr gangi fram' - Hávamál 1
$generator->dateTime()->longDate();            // '1. Gormánaðar 2026' - 24 October 2026, first day of winter
$generator->payment()->localPrice(10, 5000);   // '4.266 kr.'
```

Values were generated with seed 2026 - yours will differ.

`byname()`, `genitive()` and `runes()` are the pack's own methods - `person()` is typed with the core interface, so for static analysis call them through `$generator->ext(Person::class)` (`Quidam\DeadLanguagePack\non_IS\Person`).

```text
$ vendor/bin/quidam -l non_IS person.name -n 3 -s 2026
Freydís Þorgeirsdóttir
Sigríðr Ingólfsdóttir
Steinarr Glúmsson
```

Emails and user names are ASCII - the core transliterates `Þ`, `ð`, `ǫ`, `œ` (`hoskuldr.eiriksson@example.com`).

## Names

A Norseman's second name was his father's, in genitive, with `-son` or `-dóttir`: Leifr Eiríksson, Freydís Eiríksdóttir. Genitives aren't always "add s" - Sigurðr makes Sigurðarson, Bjǫrn Bjarnarson, Snorri Snorrason - so every name has its own (`genitive('Bjǫrn')` gives `'Bjarnar'`).

Bynames were what people actually called you: `byname()` gives one in the form of the gender - `inn rauði` / `in rauða` (the Red), `inn spaki` (the Wise), `inn djúpúðgi` / `in djúpúðga` (the Deep-minded), `hárfagri` (Fair-hair), `loðbrók` (Hairy-breeches), `blátǫnn` (Bluetooth). Titles are ranks and roles: `jarl`, `goði` (chieftain-priest), `lǫgsǫgumaðr` (lawspeaker), `vǫlva` (seeress).

## Runes

`runes()` writes a name - or any text, `runes('Leifr Eiríksson')` - in the Younger Futhark of the Viking age: `ᛚᛁᚠᚱ᛫ᛁᚱᛁᚴᛋᚢᚾ`. It had only 16 runes, so one rune serves several sounds (`ᚢ` is u, o, ǫ, y and w; `ᛏ` is t and d), double letters are written once, and words are separated by `᛫`.

## Old Icelandic calendar

`longDate()` and `monthName()` follow the calendar of the Icelandic Commonwealth, still printed in Icelandic almanacs: a year of two seasons and twelve 30-day months.

| Season | Months (first day) |
|---|---|
| Summer - from the Thursday between 19 and 25 April | Harpa, Skerpla, Sólmánuðr, *4 extra nights (aukanætr)*, Heyannir (a Sunday), Tvímánuðr, Haustmánuðr |
| Winter - 26 weeks later, a Saturday in late October | Gormánuðr, Ýlir, Mǫrsugr, Þorri (a Friday), Gói (a Sunday), Einmánuðr (a Tuesday) |

Twelve months and four extra nights make 364 days - exactly 52 weeks, so every month starts on the same weekday every year. The year falls behind the sun by a day or two each year, and every five to seven years a leap week (`sumarauki`) is added to midsummer. Dates are written with the month in genitive and the year AD: `1. Hǫrpu 2026` (23 April 2026), `2. aukanátta 2026`, `5. sumarauka 2023`. The tests check every month start from 2000 to 2039 against its traditional weekday.

Day names are the Norse week - `mánadagr`, `týsdagr` (Týr's day), `óðinsdagr`, `þórsdagr`, `frjádagr`, `laugardagr` (bath day), `sunnudagr`. The locale's long date format doesn't apply.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Iceland: `+354`, ISK `1.234 kr.` (no decimals), `12.10.2026`, `Atlantic/Reykjavik`, IBANs `IS` | - |
| `Person` | 48 men's names with genitives, 27 women's names, 14 bynames in both genders, ranks as titles | `lastName()` - patronymic; `byname()`, `genitive()`, `runes()` |
| `Address` | 36 towns in 12 lands (Ísland, Nóregr, Grœnland, Vínland, Garðaríki, Miklagarðr...) with coordinates of the site today, 11 streets, 10 things (assemblies) as counties, Icelandic postcodes | `cityData('IR')` |
| `DateTime` | Old Icelandic months, Norse day names | Old Icelandic calendar - `monthName()`, `longDate()` |
| `Company` | partnerships (`ok synir` - and sons, `ok félagar`), farms, ships and workshops, trades, lines of the Hávamál as catch phrases | business names with the owner's name in genitive |

## Data sources

Names, places and things are well-known ones from the sagas and the Viking age (Leifsbúðir is L'Anse aux Meadows, Jórvík is York, Miklagarðr Constantinople). Catch phrases are lines of the Hávamál (13th century, public domain). Coordinates are of the sites (or modern towns on them), rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
