# Gothic language pack (got_IT)

A pack for fun, next to [Old Norse](../non_IS/README.md) and [Old English](../ang_GB/README.md). Gothic is the oldest Germanic language with real texts: Bishop Wulfila translated the Bible into it in the 4th century and made an alphabet for it. Data is the Gothic kingdoms, from the Black Sea to Toledo: names with the father's name, Wulfila's alphabet and numerals, towns, and the Lord's Prayer. Country conventions are those of Italy, where Theoderic the Great ruled the Ostrogoths from Ravenna and where the Silver Bible (Codex Argenteus) was most likely written.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\got_IT\GotItProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new GotItProviderPack());

$generator->person()->name(Gender::Male);      // 'Alareiks sa Amalareikis' - Alaric, the one of Amalaric
$generator->person()->name(Gender::Female);    // 'Amalaberga so Ataulfis' - Amalaberga, the one of Ataulf
$generator->person()->gothic();                // '𐌰𐌼𐌰𐌻𐌰𐌱𐌴𐍂𐌲𐌰 𐍃𐍉 𐌰𐌹𐍂𐌼𐌰𐌽𐌰𐍂𐌴𐌹𐌺𐌹𐍃' - Amalaberga so Airmanareikis
$generator->person()->title(Gender::Male);     // 'gudja' - priest
$generator->address()->address();              // 'Gatwo Smiþane ·𐌱·, Carcaso' - Smiths' Street 2, Carcassonne
$generator->address()->cityData('IT');         // ['city' => 'Verona', 'state' => 'Italia', 'county' => 'gawi Liubagildis', 'postcode' => '84062', ...]
$generator->address()->numeral(444);           // '·𐍅𐌼𐌳·'
$generator->company()->company();              // 'Haims Totilins' (Totila's village), 'Teja jah Þiudamers'
$generator->company()->jobTitle();             // 'bokareis' - scribe
$generator->company()->catchPhrase();          // 'Weihnai namo þein' - hallowed be thy name
$generator->payment()->localPrice(10, 5000);   // '620,99 €'
```

Values were generated with seed 2026 - yours will differ.

`gothic()`, `genitive()` and `numeral()` are the pack's own methods - `person()` and `address()` are typed with the core interfaces, so for static analysis call them through `$generator->ext(Person::class)` / `$generator->ext(Address::class)` (`Quidam\DeadLanguagePack\got_IT\...`).

```text
$ vendor/bin/quidam -l got_IT person.name -n 3 -s 2026
Amalaswinþa so Widimeris
Amalaswinþa so Alareikis
Amalareiks sa Gainins
```

Spelling is the usual transliteration of Wulfila's alphabet, without the editors' accents and macrons: `þ` is th, `ƕ` is hw, `q` is kw, `ai`, `au`, `ei` are single sounds. Emails and user names are ASCII - the core transliterates `þ` and `ƕ` (`thiudareiks.sathiudameris88@example.net`).

## Names

Gothic names have no surnames. The pack names people as Wulfila's Bible does - "Iakobus sa Zaibaidaiaus", James the one of Zebedee: the given name, the article of the gender (`sa` for a man, `so` for a woman) and the father's name in genitive. Strong names in `-s` take `-is` (`Þiudareiks` → `Þiudareikis`), weak names in `-a` take `-ins` (`Wulfila` → `Wulfilins`) - `genitive('Totila')` gives `'Totilins'`.

Names are of Gothic kings and queens of Italy, Gaul and Spain (Þiudareiks - Theoderic, Alareiks - Alaric, Amalaswinþa - Amalasuntha, Totila, Wamba), of Bishop Wulfila and of Merila, a scribe who signed a Gothic deed in Naples, in their usual Gothic reconstructions. Titles are ranks and roles of the Bible and the deeds: `þiudans` (king), `kindins` (governor), `aipiskaupus` (bishop), `bokareis` (scribe); for women `qens` (wife), `magaþs` (maiden), `widuwo` (widow). Suffixes are royal houses: `us Amalam` (of the Amals), `us Balþam` (of the Balths).

## Alphabet and numerals

`gothic()` writes a name - or any text, `gothic('Wulfila')` - in Wulfila's alphabet: `𐍅𐌿𐌻𐍆𐌹𐌻𐌰`. It was made from Greek with some Latin and runic letters, one letter per letter of the transliteration (`ai` is `𐌰𐌹` in Gothic too).

The letters were also numbers, Greek-style: `𐌰`-`𐌸` are 1-9, `𐌹`-`𐍁` are 10-90 and `𐍂`-`𐍊` are 100-900. Two letters, `𐍁` (90) and `𐍊` (900), were never used as letters. Numbers were written between dots, so house numbers look like `·𐌹𐌱·` (12) - `numeral(444)` gives `·𐍅𐌼𐌳·`.

## Addresses

Goths lived in Roman towns, and we know the towns only by their Roman names. Addresses use those, with the coordinates of the site today: Ravenna and Verona in `Italia`, Tolosa and Narbo in `Gallia`, Toletum and Reccopolis (founded by King Liuvigild, *Liubagilds*) in `Spania`, Nicopolis ad Istrum (where Wulfila's Goths settled) in `Moesia`, and Doros in the Crimea, in `Gutþiuda` - the Goths' own word for their land, from the Gothic calendar. Streets (`gatwo`) are of the king, the bishop, fishers, smiths, grain, wine and bread. Districts (`gawi`) are named after their lords.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Italy: `+39`, EUR `1.234,56 €`, `12/10/2026`, `Europe/Rome`, IBANs `IT` | - |
| `Person` | 23 men's names, 7 women's names, ranks and roles as titles, royal houses as suffixes | `lastName()` - father's name; `genitive()`, `gothic()` |
| `Address` | 20 towns in 5 lands with coordinates of the site today, 8 streets, districts of their lords, house numbers in Gothic numerals, Italian postcodes | `numeral()`, `cityData('SP')` |
| `Company` | partnerships (`jah sunjus is` - and his sons), houses, vineyards and ships of their owners, trades of the Bible and the deeds, the Lord's Prayer as catch phrases | business names with the owner's name in genitive |

There's no calendar: the only Gothic month name that survived is `fruma Jiuleis` (November, "the first Yule month"), so dates use the core.

## Data sources

Names are of the Gothic kings, queens and bishops, in the reconstructed Gothic forms the handbooks give. Words, catch phrases and the alphabet are from Wulfila's Bible (4th century), the Gothic calendar and the deeds of Naples and Arezzo (6th century), all public domain: the catch phrases are lines of the Lord's Prayer (Matthew 6:9-13), and `Skapjam matjan jah drigkan` ("let's make to eat and drink") is the Gothic toast quoted in a Latin poem, *De conviviis barbaris*. Street names are put together from words of the Bible. Coordinates are of the sites (or modern towns on them), rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
