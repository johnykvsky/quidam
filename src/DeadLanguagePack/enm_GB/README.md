# Middle English language pack (enm_GB)

A pack for fun, between [Old English](../ang_GB/README.md) and [Shakespearean English](../en_XS/README.md). Data is Chaucer's England, c. 1100-1500: given names with bynames, houses known by their signs, dates by feasts and years of the reign, money in pounds, shillings and pence. Spelling is Middle English, as in Chaucer ("Caunterbury", "Aprill"). Country conventions are those of the United Kingdom.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\enm_GB\EnmGbProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new EnmGbProviderPack());

$generator->person()->name(Gender::Male);      // 'Hugh Fitz Water' - Hugh, son of Walter
$generator->person()->name(Gender::Female);    // 'Avice Whyte'
$generator->person()->title(Gender::Female);   // 'Lady'
$generator->address()->address();              // 'Tamysestrete, atte signe of the Rose, Londoun' - Thames Street
$generator->address()->cityData('KE');         // ['city' => 'Rouchestre', 'state' => 'Kent', 'county' => 'the parisshe of Seint Margarete', ...]
$generator->company()->company();              // 'the Mistere of Goldsmythes', 'Johane le Cook and Roger le Mullere'
$generator->company()->jobTitle();             // 'Carpenter' - one of the Canterbury pilgrims
$generator->company()->catchPhrase();          // 'Whan that Aprill with his shoures soote'
$generator->dateTime()->longDate();            // 'the xvj day of August, the xxxv yeer of Quene Elizabeth the Seconde'
$generator->payment()->localPrice(1, 20);      // 'xviij li. xj s.' - £18 11s.
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l enm_GB person.name -n 3 -s 2026
Isabel atte Style
Edith atte Hulle
Laurence le Clerk
```

## Names

Surnames weren't hereditary yet - a byname told people apart, and the same kinds became today's surnames: by trade (`le Smyth`, `le Taillour`, `le Webbe`), by place (`atte Welle` - at the well, `atte Wode`, `de Ware`, `of Bathe`), by father (`Fitz Water`, `Robynson`) or by looks and character (`Longe`, `Whyte`, `Lightfoot`). `Shakespere` is among them - the byname is recorded from the 13th century. Titles are `Sire`, `Maister`, `Frere` (friar), `Daun` (for monks, as Chaucer's "daun Piers"), `Dame`, `Lady` and `Prioresse`.

## Signs, not numbers

Houses had no numbers - a sign over the door was the address, and the pack writes them so: `Chepe, atte signe of the Tabard, Londoun` (Chaucer's pilgrims set out from the Tabard in Southwark). `buildingNumber()` returns the sign (`Whyte Hart`, `Grene Dragoun`, `Thre Cranes`), counties are parishes (`the parisshe of Seint Botulf`) and streets are those of medieval London - `Chepe` (Cheapside), `Bredstrete`, `Lumbardstrete`, `Sopereslane`.

## Dates

Charters were dated by feasts and by the year of the reign, so `longDate()` is too: `the xij day of Octobre, the v yeer of Kyng Charles the Thridde`, and on feast days the feast - `Michelmasse` (29 September), `Lammasse` (1 August), `Candelmasse`, `Martynmasse`, `Cristemasse`. Regnal years count from the accession day (Charles III: 8 September 2022), back to Queen Victoria; earlier dates get the year of our Lord - `the yeer of oure Lord mccclxxxvij`. The locale's long date format doesn't apply; dates are not converted to the Julian calendar (the [Shakespearean](../en_XS/README.md) pack does that).

Numerals are written as scribes did - lowercase, 4 as `iiij`, a final `i` as `j`: `j`, `iiij`, `xij`, `mcdxliiij`.

## Money

Medieval accounts counted in pounds (`li.`, libra), shillings (`s.`, solidus) and pence (`d.`, denarius) - 20 shillings to the pound, 12 pence to the shilling. `formatPrice()` and `localPrice()` convert pounds to that: `formatPrice(3.3334)` is `iij li. vj s. viij d.` (£3 6s. 8d., five marks), `formatPrice(0.5)` is `x s.`. Other Payment methods keep GBP.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | United Kingdom: `+44`, GBP, `12/10/2026`, `Europe/London`, IBANs `GB` | - |
| `Person` | 22 men's and 22 women's names, 44 bynames, medieval titles | - |
| `Address` | 27 towns with their shires and coordinates, 16 London streets, 19 signs, 12 parishes | `buildingNumber()` - a sign; `cityData('KE')` |
| `DateTime` | Middle English months and days, 14 feasts, reigns since 1837 | `longDate()` - feast or day, and year of the reign |
| `Company` | partnerships (`and his felawes`), 13 guilds (`the Mistere of Goldsmythes`), Chaucer's pilgrims as job titles, his lines as catch phrases | - |
| `Payment` | - | `formatPrice()` - pounds, shillings and pence |

## Data sources

Names and bynames are common ones of 13th-15th century records; towns are in spellings of the period (Everwik is York, Couentre Coventry, Lenne King's Lynn). Catch phrases are lines of Chaucer (public domain). Coordinates are of the towns today, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
