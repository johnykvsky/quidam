# Old French language pack (fro_FR)

A pack for fun, next to [Middle English](../enm_GB/README.md) and [Middle High German](../gmh_DE/README.md). Data is the France of c. 842-1300, of the Chanson de Roland and Chrétien de Troyes: given names with bynames, houses known by their signs, dates by feasts and years of grace, money in livres, sous and deniers. Spelling is Old French, as in the texts (`Rollant`, `Troies`, `Champaigne`). Country conventions are those of France.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\fro_FR\FroFrProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new FroFrProviderPack());

$generator->person()->name(Gender::Male);      // 'Pierre de Troies'
$generator->person()->name(Gender::Female);    // 'Alienor la Barbiere'
$generator->person()->title(Gender::Male);     // 'dant' - lord (of monks and clerics)
$generator->address()->address();              // 'Grant Rue, a l'ensaigne du Cygne, Arle' - at the sign of the Swan, Arles
$generator->address()->cityData('CH');         // ['city' => 'Provins', 'state' => 'Champaigne', 'county' => 'la parroisse Saint Gervais', ...]
$generator->company()->company();              // 'Lancelot le Grant et si frere', 'le mestier des Talemeliers'
$generator->company()->jobTitle();             // 'chevalier'
$generator->company()->catchPhrase();          // 'Paien unt tort e chrestiens unt dreit' - Chanson de Roland 1015
$generator->dateTime()->longDate();            // 'le XXVIII jor d'aoust, l'an de grace mil CM et XCVII'
$generator->payment()->localPrice(1, 20);      // 'IX livres XI sous VII deniers tornois'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l fro_FR person.name -n 3 -s 2026
Enide la Sage
Aelis du Pont
Pierre le Petit
```

Emails and user names are ASCII (`robert.denarbone81@example.com`).

## Names

Given names are of the heroes of the chansons de geste (Rollant, Olivier, Ogier, Guillaume, Vivien), of the Arthurian romances (Perceval, Gauvain, Lancelot, Guenievre, Enide, Laudine), of Aucassin's Nicolete and Roland's Aude, and of the Paris tax rolls (Jehan, Jehanne, Perrette, Mahaut). Old French still had two cases - Roland is `Rollanz` as the subject and `Rollant` as the object. The pack uses the object case, the one modern French kept.

Surnames weren't hereditary yet - people had bynames. Half are by place, the same for women and men: `de Troies`, `d'Orliens`, `du Pont`. The other half are by trade or looks, in the form of the gender: `le Mercier` / `la Merciere`, `le Talemelier` / `la Talemeliere` (baker), `le Blont` / `la Blonde`.

Titles are `sire`, `messire`, `mestre`, `dant`, `cuens` (count), `rois`; for women `dame`, `damoisele`, `abeesse`, `contesse`, `reïne`.

## Signs, not numbers

Houses had signs over their doors, not numbers (Paris got house numbers only in the 18th century): `a l'ensaigne du Cerf` (at the sign of the Stag), `de l'Escu de France`, `des Trois Rois`. `buildingNumber()` gives the sign, and an address reads `rue de la Harpe, a l'ensaigne du Cerf, Paris`. Streets are of medieval Paris, and some still exist (`rue de la Harpe`, `rue de la Huchete`). Towns are in Old French spelling (`Rains` - Reims, `Roem` - Rouen, `Peitiers`, `Orenge` of the Prise d'Orange), with the coordinates of the town today. Counties are Paris parishes.

## Dates

Months are `jenvier`, `fevrier`, `marz`, `avril`, `mai`, `juing`, `juignet`, `aoust`, `septembre`, `octobre`, `novembre`, `decembre`. Days are `lundi` to `samedi` and `diemenche`.

`longDate()` dates like a charter: the feast, or the day of the month, and the year of grace, with thousands in words and the rest in Roman numerals joined by `et`:

- `le XII jor d'octobre, l'an de grace deus mil et XXVI` - 12 October 2026
- `a la feste Saint Martin, l'an de grace mil CC et LX` - Martinmas 1260
- `le premier jor d'avril, l'an de grace mil et VII`

The locale's long date format doesn't apply.

## Money

`formatPrice()` writes amounts as medieval accounts did, in the livre tournois, the money of account of the kingdom: 20 sous to the livre, 12 deniers to the sou, all in Roman numerals. €1 is a livre: `formatPrice(3.33)` is `III livres VI sous VIII deniers tornois`, `formatPrice(0.05)` is `I sol tornois`. Amounts round to the denier, and nothing is `neant`.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | France: `+33`, EUR (see Money), `12/10/2026`, `Europe/Paris`, IBANs `FR` | - |
| `Person` | 32 men's names, 24 women's names, 14 place bynames, 13 trade and looks bynames in both genders, ranks as titles | `lastName()` - byname in the form of the gender |
| `Address` | 34 towns in 12 lands with coordinates of the town today, 14 streets of Paris, 14 signs, 12 parishes as counties, French postcodes | `cityData('CH')` |
| `DateTime` | Old French months and days, 13 feasts | charter dates - `longDate()` |
| `Company` | partnerships (`et si fil` - and his sons), crafts of the Livre des métiers (`le mestier des Talemeliers`), court offices and trades, lines of early French texts as catch phrases | - |
| `Payment` | livres, sous and deniers tournois | `formatPrice()` |

## Data sources

Names and places are well-known ones from the texts and the history of the period. Crafts are from Étienne Boileau's Livre des métiers (1268). Catch phrases are lines of the Strasbourg Oaths (842, the oldest French text), the Chanson de Roland, Marie de France's Chevrefoil and Aucassin et Nicolette, all public domain. Coordinates are of the towns, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
