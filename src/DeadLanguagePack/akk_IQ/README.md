# Akkadian language pack (akk_IQ)

A pack for fun, and the oldest one. Akkadian was the language of Babylon and Assyria, written in cuneiform on clay for more than two thousand years. Data is Mesopotamia from Sargon of Akkad (c. 2300 BC) to the last Babylonian astronomers: names with the father's name, cuneiform, cities from Ur to Nineveh, the Babylonian lunar calendar, and silver weighed in minas and shekels. Sumerian, the older language Akkadian lived beside, gives some names and all the temples. Spelling is the transcription of the handbooks (`Šarru-kīn`, `Ḫammurabi`, `Ea-nāṣir`). Country conventions are those of Iraq, where Babylon, Ur and Nineveh stand.

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Quidam;
use Quidam\DeadLanguagePack\akk_IQ\AkkIqProviderPack;

$generator = Quidam::create(seed: 2026)->withProvider(new AkkIqProviderPack());

$generator->person()->name(Gender::Male);      // 'Ea-nāṣir mār Iddin-Marduk' - Ea-nāṣir, son of Iddin-Marduk
$generator->person()->name(Gender::Female);    // 'Ištar-lamassī mārat Awīl-Marduk' - daughter of Awīl-Marduk
$generator->person()->cuneiform('Ḫammurabi');  // '𒄩𒄠𒈬𒊏𒁉' - ḫa-am-mu-ra-bi
$generator->person()->title(Gender::Female);   // 'nadītum' - a cloistered woman (and often a businesswoman)
$generator->address()->address();              // 'sūq tamkārī, bīt Narām-Sîn, Barsipa' - merchants' street, house of Narām-Sîn, Borsippa
$generator->address()->cityData('AK');         // ['city' => 'Ešnunna', 'state' => 'Akkadum', 'county' => 'Eanna', 'postcode' => '71984', ...]
$generator->company()->company();              // 'Šamšī-Adad u tappûšu' (and his partner), 'bīt Warad-Sîn'
$generator->company()->jobTitle();             // 'asûm' - physician
$generator->company()->catchPhrase();          // 'Šūtur eli šarrī' - "surpassing all kings", Gilgameš
$generator->dateTime()->longDate();            // 'ūmum 11 ša Araḫsamni, šanat 2307' - day 11 of Araḫsamnu, year 2307 of the Seleucid era
$generator->payment()->localPrice(10, 5000);   // '3 mana 10 šiqil 163 uṭṭet kaspim' - of silver
```

Values were generated with seed 2026 - yours will differ.

`cuneiform()` is the pack's own method - `person()` is typed with the core interface, so for static analysis call it through `$generator->ext(Person::class)` (`Quidam\DeadLanguagePack\akk_IQ\Person`).

```text
$ vendor/bin/quidam -l akk_IQ person.name -n 3 -s 2026
Šibtu mārat Sîn-iddinam
Erišti-Aya mārat Sîn-iddinam
Pūšu-kēn mār Šarru-kīn
```

Emails and user names are ASCII - the core transliterates `š`, `ṣ`, `ṭ`, `ḫ` and long vowels (`eristiaya.marsamasnasir19@example.net`).

## Names

Contracts name people by their fathers: `Ilī-iddinam mār Sîn-muballiṭ` (son of), `Lamassī mārat Pūšu-kēn` (daughter of). Many names are whole sentences about a god - `Ilī-iddinam` is "my god has given me", `Sîn-muballiṭ` "Sîn keeps alive", `Nabû-kudurri-uṣur` (Nebuchadnezzar) "Nabû, protect my heir".

Names are of kings (Šarru-kīn - Sargon, Ḫammurabi, Zimri-Līm of Mari, Aššur-bāni-apli - Ashurbanipal), queens (Šibtu, Šammu-ramat - Semiramis), Old Assyrian merchants and their wives (Pūšu-kēn, Lamassī, Tarām-Kūbi) and the heroes of the Gilgameš epic. There's also Sîn-leqi-unninni, who edited the epic, and Enḫeduanna, Sargon's daughter and the first author known by name. And there's Ea-nāṣir and Nanni - see below. Sumerian names (Gilgameš, Enkidu, Ur-Nammu, Šulgi, Gudea, Enḫeduanna) are in the mix, as they were in the cities.

Titles are `šarrum` (king), `šakkanakkum` (governor), `ṭupšarrum` (scribe), `tamkārum` (merchant), `bārûm` (diviner); for women `šarratum` (queen), `ēntum` (high priestess), `nadītum` and `sābītum` (ale-wife, like Šiduri in Gilgameš). Suffixes are `rabûm` (the elder) and `ṣeḫrum` (the younger).

## Cuneiform

`cuneiform()` writes a name - or any text, `cuneiform('Šarru-kīn')` - in cuneiform: `𒊭𒅈𒊒𒆠𒅔`. Cuneiform writes syllables, not letters, and the pack splits words the way scribes did. A consonant with its vowel is one sign (`ša`, `ru`, `ki`). A vowel with the consonant after it is another, used before a second consonant or at the end of the word. So `šarru` is `ša-ar-ru` and `Enḫeduanna` is `en-ḫe-du-an-na`. Long vowels are written as short, and alephs and letters Akkadian didn't have are left out.

Scribes had several signs for most syllables, and Sumerian word-signs besides. The pack uses one common Old Babylonian sign for each syllable, so the result reads correctly but is plainer than a real tablet.

## Babylonian calendar

`monthName()` and `longDate()` follow the Babylonian calendar. Months are lunar: each begins with the evening the first crescent is seen, the day after the new moon, so months are 29 or 30 days. The year begins with `Nisannu`, the first month starting on or after the spring equinox (20 March). When twelve months fall short of the next equinox, a second Addaru (`Addaru arkû`) is added - 7 times in 19 years, as in the Babylonian cycle the Jewish calendar inherited.

| Months | |
|---|---|
| Spring | Nisannu, Ayyāru, Simānu |
| Summer | Duʾūzu (Tammuz), Abu, Ulūlu |
| Autumn | Tašrītu, Araḫsamnu, Kislīmu |
| Winter | Ṭebētu, Šabāṭu, Addaru (Addaru arkû) |

Dates name the day, the month in genitive and the year of the Seleucid era - the era of the last cuneiform tablets, which began in the spring of 311 BC: `ūmum 1 ša Nisanni, šanat 2337` is 18 April 2026, the first day of the year 2337. Months follow the mean moon, so a date can be a day off the real sky.

Babylon had no week. The planetary week came later, from Babylonian astrology, so `dayOfWeek()` gives the day of each planet's god: `ūm Sîn` (Moon - Monday), `ūm Nergal` (Mars), `ūm Nabû` (Mercury), `ūm Marduk` (Jupiter), `ūm Ištar` (Venus), `ūm Ninurta` (Saturn), `ūm Šamaš` (Sun - Sunday).

## Money

Mesopotamia paid in silver by weight: 180 grains (`uṭṭet`) to the shekel (`šiqil`, about 8.3 g) and 60 shekels to the mina (`mana`). `formatPrice()` counts a shekel as 10 of the currency: `formatPrice(1234.5)` is `2 mana 3 šiqil 81 uṭṭet kaspim` ("of silver"). Amounts round to the grain, and nothing is `ul mimma`.

## Ea-nāṣir

`company()` makes Old Assyrian merchant houses (`bīt Pūšu-kēn`) and partnerships: `Ea-nāṣir u mārūšu` (and his sons), `u tappûšu` (and his partner). Catch phrases are lines of Ḫammurabi's laws ("that the strong may not oppress the weak", "an eye for an eye") and of Gilgameš ("he who saw the deep"). One is `Ana Ea-nāṣir qibīma, umma Nanni-ma` - "To Ea-nāṣir say: thus says Nanni". It opens a tablet from Ur (c. 1750 BC) in which Nanni complains that Ea-nāṣir sold him bad copper and was rude to his messenger - the oldest known customer complaint.

## What's in it

| | Data | Own methods |
|---|---|---|
| Locale | Iraq: `+964`, IQD (see Money), `12/10/2026`, `Asia/Baghdad`, IBANs `IQ` | - |
| `Person` | 30 men's names, 13 women's names, ranks and roles as titles | `lastName()` - father's name; `cuneiform()` |
| `Address` | 27 cities in 6 lands (Šumerum, Akkadum, Aššur, Mari, Ḫatti, Elamtum) with coordinates of the site today, 10 streets, houses by owner, 10 temples as counties, Iraqi postcodes | `cityData('AS')` |
| `DateTime` | Babylonian months, days of the planets' gods | Babylonian calendar - `monthName()`, `longDate()` |
| `Company` | merchant houses and partnerships, trades, Ḫammurabi, Gilgameš and Nanni as catch phrases | - |
| `Payment` | silver in minas, shekels and grains | `formatPrice()` |

## Data sources

Names, places, titles and words are well-known ones from the handbooks of Akkadian and the history of Mesopotamia. Catch phrases are from the Code of Ḫammurabi (c. 1750 BC), the Standard Babylonian Gilgameš epic and Nanni's letter (British Museum 131236), all public domain. Sign values are those of the standard sign lists. The calendar follows the mean synodic month (29.530589 days) from a new moon of 6 January 2000. Coordinates are of the sites, rounded to ~100 m. Like the other fun packs, the data comes from general knowledge, not from a curated dataset.
