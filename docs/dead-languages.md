# Dead languages

*Quidam* is Latin, so the generator speaks Latin too - and ten more languages nobody speaks any more, from the Akkadian of Babylon to the English of Shakespeare. There's one more that nobody ever spoke natively: Klingon. These packs are in `Quidam\DeadLanguagePack`, apart from the living [pl_PL](../src/LanguagePack/pl_PL/README.md).

They're for fun - test data with character, names for a fantasy game, a demo that makes people smile. They also show what the extension points can do. Every pack is built only from public extension points. The core knows them only for `ancient()`. They carry their own scripts, calendars and money in a few classes.

```php
use Quidam\DeadLanguagePack\la_VA\LaVaProviderPack;
use Quidam\Quidam;

$generator = Quidam::create(seed: 2026)->withProvider(new LaVaProviderPack());

$generator->person()->name();       // 'Quintus Tullius Scipio'
$generator->dateTime()->longDate(); // 'a.d. IV Id. Oct. MMXXVI' - 12 October 2026
```

When you don't care which one, `ancient()` picks a random historical language - any pack but Klingon. It uses the generator's randomizer, so the same seed always picks the same language:

```php
$generator = Quidam::create(seed: 2026)->ancient();

$generator->person()->name();                            // 'Amalaswinþa so Widimeris' - Gothic, this time
$generator->ext(LocaleInterface::class)->languageCode(); // which one it is - 'got'
```

Like `secure()` and every `with*()`, it returns a new generator and leaves the original as it was. For a given language - or Klingon - use `withProvider()`, or `-l la_VA` on the command line.

```text
$ vendor/bin/quidam -l akk_IQ person.name -n 3 -s 2026
Šibtu mārat Sîn-iddinam
Erišti-Aya mārat Sîn-iddinam
Pūšu-kēn mār Šarru-kīn
```

## The packs

Oldest first. Each pack's README has the full story - its names, calendar and sources.

| Pack | Language | When and where | Highlights |
|---|---|---|---|
| [akk_IQ](../src/DeadLanguagePack/akk_IQ/README.md) | Akkadian | Babylon and Assyria, c. 2300 BC - AD 100 | names in cuneiform, the Babylonian lunar calendar, silver in minas and shekels, Ea-nāṣir's bad copper |
| [grc_GR](../src/DeadLanguagePack/grc_GR/README.md) | Ancient Greek | classical Athens and the Greek world | names with father and deme, the Attic calendar dated by Olympiads, Greek numerals |
| [la_VA](../src/DeadLanguagePack/la_VA/README.md) | Latin | classical Rome and its provinces | praenomen, nomen and cognomen, the Roman calendar (kalends, nones, ides), Roman numerals |
| [got_IT](../src/DeadLanguagePack/got_IT/README.md) | Gothic | the Gothic kingdoms, 4th-6th century | Wulfila's alphabet and numerals, father's names with real Gothic genitives, the Lord's Prayer |
| [ang_GB](../src/DeadLanguagePack/ang_GB/README.md) | Old English | Anglo-Saxon England, c. 450-1100 | names built from two elements, the seven kingdoms, Bede's month names |
| [non_IS](../src/DeadLanguagePack/non_IS/README.md) | Old Norse | the Viking age, from Vínland to Miklagarðr | patronymics and bynames, Younger Futhark runes, the Old Icelandic calendar |
| [fro_FR](../src/DeadLanguagePack/fro_FR/README.md) | Old French | France, c. 842-1300 | heroes of the chansons de geste, house signs, years of grace, livres, sous and deniers tournois |
| [gmh_DE](../src/DeadLanguagePack/gmh_DE/README.md) | Middle High German | the Empire, c. 1050-1350 | Nibelungenlied names, named houses, Old German months, charter dates in words |
| [enm_GB](../src/DeadLanguagePack/enm_GB/README.md) | Middle English | Chaucer's England, c. 1100-1500 | bynames, inn signs, feast days and regnal years, pounds, shillings and pence |
| [en_XS](../src/DeadLanguagePack/en_XS/README.md) | Shakespearean English | Shakespeare's England and his plays | his characters, Old Style dates, pounds, shillings and pence, insults |
| [tlh_QO](../src/DeadLanguagePack/tlh_QO/README.md) | Klingon | the Klingon Empire - fiction | warriors named by their fathers, Klingon numbers, darseks, stardates |

## A taste of each

Examples are from the packs' READMEs and tests. Names were generated with seed 2026, so yours will differ.

| Pack | A name | A date | A price, address or line |
|---|---|---|---|
| akk_IQ | `Ea-nāṣir mār Iddin-Marduk` | `ūmum 1 ša Nisanni, šanat 2337` | `2 mana 3 šiqil 81 uṭṭet kaspim` |
| grc_GR | `Perikles Isokratous Lamptreus` | `θʹ Maimakterionos, Ol. χϟϛʹ, etos αʹ` | `Ktema es aiei` |
| la_VA | `Quintus Tullius Scipio` | `a.d. IV Id. Oct. MMXXVI` | `Vicus Sandaliarius IV, Sparta` |
| got_IT | `Alareiks sa Amalareikis` | - | `Gatwo Smiþane ·𐌱·, Carcaso` |
| ang_GB | `Beorhtweard Hererīcing` | `12 Winterfylleþ, anno mmxxvi` | `Earninga strǣt eahta, Wigoraceaster` |
| non_IS | `Hǫskuldr Eiríksson` | `1. Gormánaðar 2026` | `ᚦᚢᚱᚢᛚᚠᚱ᛫ᛋᛏᛁᚾᛅᚱᛋᚢᚾ` |
| fro_FR | `Alienor la Barbiere` | `le XII jor d'octobre, l'an de grace deus mil et XXVI` | `III livres VI sous VIII deniers tornois` |
| gmh_DE | `Elsebêt diu Smidinne` | `an sant Martînes tage, dô man zalte von gotes gebürte zwei tûsent sehs und zweinzic jâr` | `Kornmarket, hûs zem Swanen, Wormez` |
| enm_GB | `Hugh Fitz Water` | `the xvj day of August, the xxxv yeer of Quene Elizabeth the Seconde` | `iij li. vj s. viij d.` |
| en_XS | `Antonio Dogberry` | `the eight and twentieth day of October, in the year of our Lord 1980` | `Thou cream-faced loon!` |
| tlh_QO | `Makh, son of M'Rokh` | `Stardate 2026.285` | `714 darsek` |

## What they have in common

**Names before surnames.** Hereditary surnames are a late invention. The packs name people the way their times did: by the father (`Leifr Eiríksson`, `Alareiks sa Amalareikis`, `Ilī-iddinam mār Sîn-muballiṭ`), by place (`Walther von der Vogelweide`, `de Troies`), by trade (`Johan le Smyth`, `diu Smidinne`) or by looks (`Eiríkr inn rauði` - the Red). Where the byname changes with gender, `lastName()` uses the gender's form, like the Polish pack does.

**Scripts.** The data is in Latin letters, so names work everywhere: in emails, slugs, the command line. Where the language had a script of its own, the pack's `Person` has a method that writes in it:

| Pack | Method | Example |
|---|---|---|
| non_IS | `runes()` - Younger Futhark | `runes('Leifr Eiríksson')` → `ᛚᛁᚠᚱ᛫ᛁᚱᛁᚴᛋᚢᚾ` |
| got_IT | `gothic()` - Wulfila's alphabet | `gothic('Wulfila')` → `𐍅𐌿𐌻𐍆𐌹𐌻𐌰` |
| akk_IQ | `cuneiform()` - syllables, as scribes split them | `cuneiform('Šarru-kīn')` → `𒊭𒅈𒊒𒆠𒅔` (ša-ar-ru-ki-in) |

Numbers get the same treatment: Roman (`la_VA`, medieval lowercase `iiij` in `enm_GB`), Greek (`grc_GR`), Gothic (`got_IT`) and Klingon (`tlh_QO`) numerals show up in house numbers and dates.

**Calendars.** `longDate()` dates as the time did, and `monthName()` and `dayOfWeek()` follow. You get kalends, nones and ides; Attic months and Olympiads; the Old Icelandic calendar of two seasons; Babylonian lunar months with a leap month; feast days and regnal years; and charter dates in words. All of them are computed, not looked up, and the tests check them against known dates. `localDate()` keeps the numeric format of the country.

**Money.** Packs with money of their own override `Payment::formatPrice()`, so `localPrice()` gives pounds, shillings and pence (`enm_GB`, `en_XS`), livres, sous and deniers (`fro_FR`), silver by weight (`akk_IQ`) or darseks (`tlh_QO`). The others use the currency of their country.

**Real places.** Addresses use real towns under their old names, with the coordinates of the site today - Jórvík is York, Bābilim is Babylon, Wormez is Worms. `cityData()` and `addressData()` take a region code of the pack (`cityData('IR')` - Írland). Houses mostly had signs or names, not numbers, and the packs keep that: `atte signe of the Tabard`, `hûs zem Swanen`, `bīt Ea-nāṣir` (the house of Ea-nāṣir).

**Country conventions.** A pack needs a real country for its phone numbers, IBANs and timezone, so each borrows one: the Holy See for Latin, Iceland for Old Norse, Iraq for Akkadian, Italy for Gothic (Theoderic's Ravenna). The pack code says which - `got_IT`. Shakespearean English has no ISO language code of its own, so its pack code uses `XS`, a user-assigned country code, with the United Kingdom's conventions.

**Own methods.** `runes()`, `cuneiform()`, `insult()`, `byname()`, `genitive()` and others are not in the core interfaces. `$generator->person()` is typed with the interface, so for static analysis call them through the pack's class: `$generator->ext(\Quidam\DeadLanguagePack\non_IS\Person::class)->runes()`.

## Klingon

Klingon isn't dead - it was never alive. Marc Okrand made it for Star Trek in 1984, and it has fans who speak it. Its pack lives here too, with the other languages nobody speaks natively, but `ancient()` never picks it - Klingon is many things, ancient isn't one of them. `tlh` is its real ISO 639 code, and `QO` (for Qo'noS) is a user-assigned country code no real country will ever get. Paramount holds the copyright on the Klingon dictionary, so the pack uses a small word list of its own, and it's an unofficial fan pack.

## Data and accuracy

The packs come from general knowledge, not from curated datasets. Names, places, words and quotes are well-known ones, and the quotes come from public-domain texts: Hávamál, the Chanson de Roland, Chaucer, the Code of Ḫammurabi, Wulfila's Bible. Where a pack simplifies, its README says so. The cuneiform uses one sign per syllable, the Babylonian calendar follows the mean moon, and Gothic has no calendar because only one Gothic month name survives. These are fun packs - good enough to be right, not good enough to cite.

## Writing your own

A dead language pack is written like any other - see [Extending](extending.md#provider-pack---a-language-pack) and the [pl_PL](../src/LanguagePack/pl_PL/README.md) template. Put it in its own folder with a `ProviderPack` and subclasses of the core extensions, and the command line finds it by its code. A historical pack in `src/DeadLanguagePack` also needs an entry in `AncientLanguages::PACKS` for `ancient()` - a test fails until it has one. The folder and code are `xx_XX` or `xxx_XX`: an ISO 639 language code and the country whose conventions the pack borrows, or a user-assigned code (`AA`, `QM`-`QZ`, `XA`-`XZ`, `ZZ`) when there's none. The locale must still name a real country, because timezones and IBANs need one. Each pack here has tests and a seed snapshot that covers every public method, and the sweep test checks all of them for clean values.
