# Changelog

All notable changes to this project are documented here. The project follows [Semantic Versioning](https://semver.org).

Seeded output (values produced for a given seed and clock) is stable across **patch** releases. Minor and major releases may change it - every change is listed under **Seeded output changes**. See [Seed stability](docs/quidam.md#seed-stability).

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

[0.0.1]: https://github.com/johnykvsky/quidam/releases/tag/v0.0.1
