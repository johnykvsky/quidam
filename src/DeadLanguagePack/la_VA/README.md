# Latin language pack (la_VA)

A pack for fun - Quidam is a Latin word, so its generator speaks Latin too. Data is classical Rome: names, the cities of the provinces, the Roman calendar. Country conventions are those of the Holy See, the one state where Latin is still official. Like [pl_PL](../../LanguagePack/pl_PL/README.md), it uses only public extension points; the core knows it only as one of the languages `ancient()` picks from.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\la_VA\LaVaProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new LaVaProviderPack());

$generator->person()->name(Gender::Male);      // 'Quintus Tullius Scipio' - praenomen, nomen, cognomen
$generator->person()->name(Gender::Female);    // 'Paulla Cornelia' - feminine form of the nomen
$generator->person()->title(Gender::Male);     // 'Consul'
$generator->address()->address();              // 'Vicus Sandaliarius IV, Sparta' - Roman numerals, no postcode
$generator->address()->cityData('BR');         // ['city' => 'Camulodunum', 'state' => 'Britannia', 'county' => 'tribus Arnensis', ...]
$generator->company()->company();              // 'Fullonica Papirii' - the fullery of Papirius
$generator->company()->jobTitle();             // 'Pictor'
$generator->company()->catchPhrase();          // 'Mens sana in corpore sano'
$generator->dateTime()->longDate();            // 'a.d. IV Id. Oct. MMXXVI' - 12 October 2026
$generator->dateTime()->dayOfWeek();           // 'dies Lunae'
$generator->payment()->localPrice(10, 5000);   // '2.452,54 €'
```

Values were generated with seed 2026 - yours will differ.

```text
$ vendor/bin/quidam -l la_VA person.name -n 3 -s 2026
Quarta Flavia
Publia Furia
Sextus Plinius Drusus
```

## Roman calendar

`longDate()` writes dates the Roman way. Days are counted down, inclusively, to the next of three marker days of the month: the Kalends (1st), the Nones (5th, or 7th in March, May, July and October) and the Ides (13th or 15th). The year is AD, in Roman numerals:

| Date | `longDate()` | Read as |
|---|---|---|
| 1 January 2026 | `Kal. Ian. MMXXVI` | on the Kalends of January |
| 5 March 2026 | `a.d. III Non. Mart. MMXXVI` | third day before the Nones of March |
| 14 March 2026 | `prid. Id. Mart. MMXXVI` | the day before the Ides of March |
| 15 March 2026 | `Id. Mart. MMXXVI` | the Ides of March |
| 16 March 2026 | `a.d. XVII Kal. Apr. MMXXVI` | 17th day before the Kalends of April |
| 24 February 2024 | `a.d. bis VI Kal. Mart. MMXXIV` | the leap day - "twice the sixth" |

In leap years Romans doubled the 24th of February (*bis sextum*, hence "bissextile"), so the 25th-29th have the day counts of a common year's 24th-28th. The locale's long date format doesn't apply. Month and day names (`monthName()`, `dayOfWeek()`) are Latin: `Ianuarius`... `December`, `dies Lunae`... `dies Solis`.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Vatican City: `+39`, EUR `1.234,56 €`, `12/10/2026`, `Europe/Vatican`, IBANs `VA` | - |
| `Person` | 20 praenomina, 25 women's names, 37 nomina, 39 cognomina, offices as titles (`Consul`, `Virgo Vestalis`) | `lastName()` - "Iulius Caesar" for men, "Iulia" for women; `cognomen()` |
| `Address` | 122 cities of Italy and 20 provinces (2nd century) with coordinates of the site today, 31 streets and roads, the 35 tribes as counties | building numbers and flats (`insula`, `cenaculum`, `taberna`) in Roman numerals; `cityData('BR')` |
| `DateTime` | Latin months, planetary week days | `longDate()` in the Roman calendar |
| `Company` | partnerships (`et Filii`, `et Socii`), shops (`Taberna`, `Fullonica`), trades, fields, mottos as catch phrases | shop names with the owner's nomen in genitive |

Romans had no postcodes - `postcode()` is `00120`, the only one of the Vatican, and address lines have none. Country names, emails, phone numbers and IBANs come from the core with Vatican conventions; `lorem()` is Latin already.

## Data sources

Names and places are well-known classical ones - praenomina, gentes and cognomina of the Republic and Empire, cities and provinces as of the 2nd century AD, the 35 tribes. Coordinates are of the sites (or modern cities on them), rounded to ~100 m.
