# Middle High German language pack (gmh_DE)

A pack for fun, next to [Middle English](../enm_GB/README.md) and [Old French](../fro_FR/README.md). Data is the German-speaking lands of c. 1050-1350, the age of the Nibelungenlied, Parzival and the Minnesang: given names with bynames, town houses known by their names, the Old German months, dates as charters wrote them. Spelling is normalized Middle High German, as in school editions (`Sîvrit`, `Kriemhilt`, `Wormez` - circumflexes mark long vowels). Country conventions are those of Germany.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\gmh_DE\GmhDeProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new GmhDeProviderPack());

$generator->person()->name(Gender::Male);      // 'Eckehart von Bechelâren'
$generator->person()->name(Gender::Female);    // 'Elsebêt diu Smidinne' - Elsebêt the (woman) smith
$generator->person()->title(Gender::Female);   // 'küneginne' - queen
$generator->address()->address();              // 'Mülngazze, hûs zem Hirze, Ulme' - Mill Lane, the house At the Stag, Ulm
$generator->address()->cityData('OS');         // ['city' => 'Medelicke', 'state' => 'Ôsterrîche', 'county' => 'bistuom ze Kostenze', ...]
$generator->company()->company();              // 'Gêrnôt der Vischer und sîne bruoder', 'diu zunft der Becken'
$generator->company()->jobTitle();             // 'koufman' - merchant
$generator->company()->catchPhrase();          // 'Under der linden an der heide' - Walther von der Vogelweide
$generator->dateTime()->longDate();            // 'an dem 28. tage des Ougestes, dô man zalte von gotes gebürte tûsent niun hundert siben und niunzic jâr'
$generator->dateTime()->monthName();           // 'Hornunc' - February
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l gmh_DE person.name -n 3 -s 2026
Herzeloyde diu Schrîberinne
Herzeloyde von Strâzburc
Rûdolf der Smit
```

Emails and user names are ASCII - the core transliterates `â`, `ê`, `î`, `ô`, `û`, `æ`, `œ` (`hildebrant.voneschenbach81@example.com`).

## Names

Given names are of the heroes and heroines of the epics and romances (Sîvrit, Kriemhilt, Hagene, Rüedegêr, Parzivâl, Isôt) and of the poets and their patrons (Walther, Wolfram, Hartman, Liupolt). Surnames weren't hereditary yet - people had bynames. Half are by place, the same for women and men: `von der Vogelweide`, `von Eschenbach`, `von Tronege` (Hagen's). The other half are by trade or looks, in the form of the gender: `der Smit` / `diu Smidinne`, `der Becke` / `diu Beckinne` (baker), `der Küene` / `diu Küene` (the bold).

Titles are `her` (sir - "her Walther"), `meister`, `grâve`, `künec`; for women `vrouwe`, `juncvrouwe`, `eppetissinne` (abbess), `küneginne`.

## House names, not numbers

Medieval German town houses had names, painted or carved over the door, and some still have them: `hûs zem Swanen` (the house At the Swan), `zem guldînen Swerte` (At the Golden Sword), `zer Krônen` (At the Crown - `zer` for feminine nouns). `buildingNumber()` gives the house name, and an address reads `Kornmarket, hûs zem Swanen, Wormez`.

Towns are in Middle High German spelling, with the coordinates of the town today. They include the Nibelungs' road from Santen (Xanten, Sîvrit's home) and Wormez (Worms, the Burgundian court) down the Danube to Bechelâren (Pöchlarn, Rüedegêr's seat) and Wiene. Counties are dioceses: `bistuom ze Pazzouwe`.

## Dates

Month names are the Old German ones that Charlemagne gave the months, in their Middle High German forms: `Hartmânôt` (January), `Hornunc` (February, still used in Swiss German), `Ôstermânôt`, `Meie`, `Brâchmânôt`, `Höumânôt` (hay month), `Ougest`, `Wînmânôt` (wine month), `Kristmânôt`. Days are `mântac`, `zîstac`, `mittewoche`, `donerstac`, `vrîtac`, `sameztac` and `sunnentac`.

`longDate()` dates like a charter: the feast, or the day of the month with the month in genitive, and the year of the Lord in words, units before tens as in German:

- `an dem 12. tage des Wînmânôdes, dô man zalte von gotes gebürte zwei tûsent sehs und zweinzic jâr` - 12 October 2026 ("when one counted from God's birth...")
- `an sant Martînes tage, dô man zalte von gotes gebürte tûsent zwei hundert sehzic jâr` - Martinmas 1260

The locale's long date format doesn't apply.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Germany: `+49`, EUR `1.234,56 €`, `12.10.2026`, `Europe/Berlin`, IBANs `DE` | - |
| `Person` | 31 men's names, 20 women's names, 12 place bynames, 12 trade and looks bynames in both genders, ranks as titles | `lastName()` - byname in the form of the gender |
| `Address` | 30 towns in 8 lands of the Empire with coordinates of the town today, 11 streets, 14 house names, 14 dioceses as counties, German postcodes | `cityData('OS')` |
| `DateTime` | Old German months, Middle High German day names, 14 feasts | charter dates - `longDate()` |
| `Company` | partnerships (`und sîne süne` - and his sons), craft guilds (`diu zunft der Smide`), court offices of the Nibelungenlied (`truhsæze`, `schenke`, `marschalc`) and trades, lines of the Nibelungenlied and the Minnesang as catch phrases | - |

## Data sources

Names and places are well-known ones from the epics, the romances and the history of the period. Catch phrases are lines of the Nibelungenlied (`Uns ist in alten mæren wunders vil geseit`), of Walther von der Vogelweide and of the 12th-century love verse `Dû bist mîn, ich bin dîn`, all public domain. House names are of the common kinds that were recorded in towns like Basel, Mainz and Strasbourg. Coordinates are of the towns, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
