# Shakespearean English language pack (en_XS)

A pack for fun, the youngest of the English family after [Old English](../ang_GB/README.md) and [Middle English](../enm_GB/README.md). Data is Shakespeare's England and the worlds of his plays: names of his characters and fellow players, houses known by their signs, Old Style dates, pounds, shillings and pence - and insults. Early Modern English has no ISO code of its own, so the pack code uses `XS`, a user-assigned country code; the locale is the United Kingdom's.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\en_XS\EnXsProviderPack;
use Quidam\DeadLanguagePack\en_XS\Person;

$generator = Quidam::create(seed: 2026)->withProvider(new EnXsProviderPack());

$generator->person()->name(Gender::Male);      // 'Antonio Dogberry'
$generator->person()->name(Gender::Female);    // 'Anne Montague'
$generator->person()->title(Gender::Female);   // 'Lady'
$generator->ext(Person::class)->insult();      // 'Thou cream-faced loon!' - Macbeth
$generator->address()->address();              // 'Bankside, at the sign of the Bull, Pamplona'
$generator->address()->cityData('IT');         // ['city' => 'Mantua', 'state' => 'Italy', 'county' => 'the parish of St. Giles, Cripplegate', ...]
$generator->company()->company();              // 'Alleyn & Bottom', 'The Angel Tavern' - or "The Lord Burbage's Men"
$generator->company()->jobTitle();             // 'Nurse'
$generator->company()->catchPhrase();          // "All the world's a stage"
$generator->dateTime()->longDate();            // 'the eight and twentieth day of October, in the year of our Lord 1980'
$generator->payment()->localPrice(1, 20);      // '£11 18s. 7d.'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l en_XS person.name -n 3 -s 2026
Beatrice Elbow
Olivia Fletcher
Hamnet Montague
```

## Names and insults

First names are those of Shakespeare's family (Anne, Susanna, Judith, Hamnet) and his characters (Romeo, Benedick, Rosalind, Viola, Ophelia); surnames those of his fellow players and playwrights (Burbage, Kemp, Heminges, Condell, Marlowe, Jonson) and of his clowns (Bottom, Quince, Dogberry, Aguecheek, Mistress Quickly). Titles are `Master`, `Sir`, `Goodman`, `Signior`, `Mistress`, `Goodwife`.

`insult()` - the pack's own method, reach it with `ext(Person::class)` - gives an insult as the plays have it: "Thou cream-faced loon!" (Macbeth), "You Banbury cheese!" (The Merry Wives of Windsor), "I do desire we may be better strangers." (As You Like It), "Thou lump of foul deformity!" (Richard III).

## Signs and places

Houses had signs, not numbers: `Bread Street, at the sign of the Mermaid, London` - the tavern of Jonson and his friends. `buildingNumber()` returns the sign (`Boar's Head`, `Green Dragon`, `White Greyhound` - where *Venus and Adonis* was sold). Streets are Shakespeare's - Henley Street (his birthplace), Silver Street (his lodging), Maid Lane (the Globe), Bankside, Eastcheap. Towns are those of his England (Stratford-upon-Avon, London, Windsor, Dover) and the settings of the plays (Verona, Venice, Elsinore, Dunsinane, Messina, Ephesus), with coordinates of the place today. Counties are parishes, secondary addresses include the `tiring-house`, where players dress.

## Old Style dates

Shakespeare's England kept the Julian calendar - Britain changed only in 1752, by then 11 days behind the continent - and began the year on Lady Day, 25 March. `longDate()` and `monthName()` keep Old Style for any date:

| Date | `longDate()` |
|---|---|
| 12 October 2026 | the nine and twentieth day of September, in the year of our Lord 2026 - Michaelmas |
| 3 May 1616 | the three and twentieth day of April, in the year of our Lord 1616 - the day Shakespeare died |
| 14 September 1752 | the third day of September, in the year of our Lord 1752 - the day Britain switched |
| 6 April 2026 | the four and twentieth day of March, in the year of our Lord 2025/6 - before Lady Day |
| 13 March 1900 | the nine and twentieth day of February, in the year of our Lord 1899/1900 - a Julian leap day |

Between 1 January and Lady Day letters gave both years - `2025/6`, `2029/30`, `1699/1700`. Days are ordinals as an Elizabethan would say them: "first", "twelfth", "one and twentieth". The locale's long date format doesn't apply.

## Money

Prices are in pounds, shillings and pence - 20s. to the pound, 12d. to the shilling: `formatPrice(3.3334)` is `£3 6s. 8d.`, `formatPrice(0.5)` is `10s.`. `localPrice()` uses it too; other Payment methods keep GBP.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | United Kingdom: `+44`, GBP, `12/10/2026`, `Europe/London`, IBANs `GB` | - |
| `Person` | 34 men's and 29 women's names, 39 surnames, titles of the age, 12 insults | `insult()` |
| `Address` | 25 towns of England and the plays with coordinates, 14 streets, 17 signs, 8 parishes | `buildingNumber()` - a sign; `cityData('IT')` |
| `DateTime` | - | Old Style - `monthName()`, `longDate()` |
| `Company` | players' companies (`The Lord Burbage's Men`), partnerships, taverns, trades of the plays, lines of Shakespeare as catch phrases | - |
| `Payment` | - | `formatPrice()` - pounds, shillings and pence |

## Data sources

Names, places and lines are Shakespeare's and his contemporaries' (public domain). Coordinates are of the places today, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
