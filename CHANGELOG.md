# Changelog

All notable changes to this project are documented here. The project follows [Semantic Versioning](https://semver.org).

Seeded output (values produced for a given seed and clock) is stable across **patch** releases. Minor and major releases may change it - every change is listed under **Seeded output changes**. See [Seed stability](docs/quidam.md#seed-stability).

## [0.0.2] - 2026-10-13

Dead languages: eleven language packs for fun, from the Akkadian of Babylon to Shakespeare's English - and Klingon - in `src/DeadLanguagePack`, with `ancient()` to pick one at random. See [Dead languages](docs/dead-languages.md).

### Added

- dead language packs in `Quidam\DeadLanguagePack`, each with its own README, tests and seed snapshot:
  - `akk_IQ` Akkadian - names with the father's name, cuneiform (`cuneiform()`), the Babylonian lunar calendar, silver in minas and shekels
  - `grc_GR` Ancient Greek - names with father and deme, the Attic calendar dated by Olympiads, Greek numerals
  - `la_VA` Latin - praenomen, nomen and cognomen, the Roman calendar, Roman numerals
  - `got_IT` Gothic - Wulfila's alphabet (`gothic()`) and numerals, father's names in Gothic genitive
  - `ang_GB` Old English - two-element names, the seven kingdoms, Bede's month names
  - `non_IS` Old Norse - patronymics and bynames, Younger Futhark runes (`runes()`), the Old Icelandic calendar
  - `fro_FR` Old French - house signs, years of grace, livres, sous and deniers tournois
  - `gmh_DE` Middle High German - named houses, Old German months, charter dates in words
  - `enm_GB` Middle English - inn signs, feast days and regnal years, pounds, shillings and pence
  - `en_XS` Shakespearean English - his characters, Old Style dates, pounds, shillings and pence, insults (`insult()`)
  - `tlh_QO` Klingon - an unofficial fan pack: warriors named by their fathers, Klingon numbers, darseks, stardates
- `Quidam::ancient()` - a generator speaking a random historical language (any dead language pack but Klingon), picked with the generator's randomizer, so the same seed picks the same language: `Quidam::create()->ancient()->person()->name()`. The packs it picks from are in `DeadLanguagePack\AncientLanguages::PACKS`; Klingon and any given pack load with `withProvider()`
- Transliterator: `Ǣ`, `ǣ`, `Ǽ`, `ǽ` (ae), `Ƿ`, `ƿ` (wynn - w), `Ƕ`, `ƕ` (hwair - hw) - Old English and Gothic letters in emails and slugs, dropped before
- docs: [Dead languages](docs/dead-languages.md) - all the packs, what they have in common, writing your own

### Changed

- command line: `-l` finds packs in `Quidam\LanguagePack` and `Quidam\DeadLanguagePack` (`-l pl_PL`, `-l la_VA`); errors and help list the codes of both

### Seeded output changes

- none - existing data has none of the newly transliterated letters

## [0.0.1] - 2026-10-11

Initial release - DummyGenerator's architecture (DI container, interfaces for everything, strategies, provider packs) without magic: no `__call()`, no `parse()` in the core.

### Added

- 24 extensions under interfaces, fetched with typed accessors (`$generator->person()->name()`) or `ext()`
- immutable generator: seeds, frozen clock, `fork()` for stable records, `secure()` randomness, `many()`, `sequence()`
- strategies: `unique()`, `valid()`, `optional()`, strategy chains and generator-wide strategies
- neutral, country-less defaults; country conventions in one `LocaleInterface`
- opt-in add-ons: Template, Filler, Text, RichText, EdgeCases
- Polish language pack `pl_PL` (`src/LanguagePack/pl_PL`) - an example of how to write language packs
- command line `bin/quidam` - expressions, templates, JSON / CSV, language packs (`-l`), config file (`-c`)
- built-in container with no dependencies besides `psr/clock` and `psr/container`; other containers via adapters
- `Testing\SeedSnapshot` to guard seeded output of packs and extensions

[0.0.2]: https://github.com/johnykvsky/quidam/releases/tag/v0.0.2
[0.0.1]: https://github.com/johnykvsky/quidam/releases/tag/v0.0.1
