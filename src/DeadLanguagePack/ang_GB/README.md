# Old English language pack (ang_GB)

A pack for fun, one of the English family with [Middle English](../enm_GB/README.md) and [Shakespearean English](../en_XS/README.md). Data is Anglo-Saxon England, c. 450-1100: names built from two elements, the seven kingdoms and their shires, Bede's month names and years as the Anglo-Saxon Chronicle writes them. Spelling is the usual normalized one - þ and ð (th), æ (ash), macrons on long vowels. Country conventions are those of the United Kingdom.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\ang_GB\AngGbProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new AngGbProviderPack());

$generator->person()->name(Gender::Male);      // 'Beorhtweard Hererīcing' - Beorhtweard, son of Hererīc
$generator->person()->name(Gender::Female);    // 'Ælfgifu Sigewealdes dohtor' - Ælfgifu, Sigeweald's daughter
$generator->person()->title(Gender::Female);   // 'abbudisse' - abbess
$generator->address()->address();              // 'Earninga strǣt eahta, Wigoraceaster' - Ermine Street 8, Worcester
$generator->address()->cityData('NH');         // ['city' => 'Lindisfarnea', 'state' => 'Norþhymbre', 'county' => 'Bernice', ...]
$generator->company()->company();              // 'Sigewulf and Ælfmund' - or 'Ælfrēd and his suna', 'Medoheall Ælfrēdes'
$generator->company()->jobTitle();             // 'smiþ'
$generator->company()->catchPhrase();          // 'Þæs ofereode, þisses swā mæg' - Deor
$generator->dateTime()->longDate();            // '12 Winterfylleþ, anno mmxxvi'
$generator->dateTime()->dayOfWeek();           // 'Wōdnesdæg' - Woden's day
$generator->payment()->localPrice(10, 5000);   // '£620.99'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l ang_GB person.name -n 4 -s 2026
Ælfburh Beorhthelmes dohtor
Sigeweald Cynewulfing
Beorhtburh Sigerīces dohtor
Godrēd Sigewulfing
```

Emails are ASCII - the core transliterates þ, ð, æ, ǣ and the macrons (`aethelflaed.eadgaresdohtor@example.com`).

## Names

Anglo-Saxons built names from two meaningful elements: Ælf + rēd (elf + counsel), Wulf + stān (wolf + stone), Ēad + gifu (wealth + gift), God + gifu - Lady Godiva. Families reused elements, so names are composed the same way here, from 14 first elements and 12 men's / 8 women's second elements; one element is never used twice (no "Wulfwulf").

There were no surnames. A man was his father's son, marked by "-ing" - "Ælfrēd Æþelwulfing", as the Chronicle's genealogies write it; a woman her father's daughter - "Ælfgifu Ēadgāres dohtor". `genitive('Ælfrēd')` gives `'Ælfrēdes'`.

## Dates

Month names are the ones Bede recorded in *De temporum ratione* (725): Æfterra Gēola ("after Yule", January), Hrēþmōnaþ (March), Ēastermōnaþ (April, the month of the goddess Ēostre), Þrimilcemōnaþ ("three milkings", May), Winterfylleþ ("winter full moon", October), Blōtmōnaþ ("sacrifice month", November). Days are named after the gods - Tīwesdæg, Wōdnesdæg, Þunresdæg, Frīgedæg.

`longDate()` writes the year in lowercase Roman numerals, as the Anglo-Saxon Chronicle does: `12 Winterfylleþ, anno mmxxvi`; the battle of Edington was fought in `anno dccclxxviii`. The locale's long date format doesn't apply.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | United Kingdom: `+44`, GBP `£1,234.56`, `12/10/2026`, `Europe/London`, IBANs `GB` | - |
| `Person` | name elements (14 first, 12 + 8 second), titles (`cyning`, `ealdormann`, `þegn`, `cwēn`, `abbudisse`) | `firstName()` - two-element names; `lastName()` - "-ing" / "dohtor"; `genitive()` |
| `Address` | 31 towns of the seven kingdoms with their shires and coordinates, Roman roads and town streets, numbers one to twelve in words | `cityData('WS')` - towns of one kingdom |
| `DateTime` | Bede's months, Old English day names | `longDate()` - Chronicle-style year |
| `Company` | partnerships (`and his suna` - and his sons), mead halls, mills and smithies, trades, lines of Old English poetry as catch phrases | business names with the owner's name in genitive |

## Data sources

Name elements, towns, shires and months are well-known ones from Anglo-Saxon sources (Eoforwīc is York, Lundenwīc London, Wintanceaster Winchester). Catch phrases are lines of *Beowulf*, *The Wanderer*, *Deor* and *The Battle of Maldon* (public domain). Coordinates are of the towns today, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
