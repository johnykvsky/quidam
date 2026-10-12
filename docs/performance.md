# Performance - Quidam, DummySlim, DummyGenerator, FakerPHP

Measured 2026-10-09. Raw data: [`bench/results/2026-10-09_213845.json`](../bench/results/2026-10-09_213845.json), scripts: [`bench/`](../bench) (`php bench/run.php`, libraries are expected in sibling directories).

> Quidam was called DummyLight before its first release - the raw result files below label it `dummylight`.
>
> DummySlim is an unreleased predecessor of Quidam (it was never published). It is kept in these results as a speed reference only - `bench/` no longer measures it.
>
> "Quidam (PHP-DI)" is the PHP-DI adapter Quidam used by default until PHP-DI was dropped as a dependency - it lives on as an example in [containers.md](containers.md); `bench/` now measures one Quidam (built-in container).
>
> Measured before the neutral locale defaults (`LocaleInterface`, transliteration in `Internet`, address / phone / company templates) - Quidam output was then identical to DummySlim's. Re-measured on 2026-10-11 - see [Re-run 2026-10-11](#re-run-2026-10-11---neutral-locale-built-in-container-only-add-ons-pl_pl) at the end.

Quidam is measured twice - on PHP-DI (default) and on the built-in container (`Quidam::create(containerFactory: new SimpleContainerFactory())`). Everything else is the same code.

## Summary

* **Per operation Quidam runs at DummySlim speed on either container** - in the 26 shared value scenarios within 1-4% of DummySlim (built-in container mostly within 1%). PHP-DI adds ~10 ns per `ext()` (101 vs 91 ns), visible only on the cheapest operations (`randomElement`, `uuid` 4-10% slower than on the built-in container). DummyGenerator was slow because of `__call()` and `parse()`, not PHP-DI.
* **Where the container matters - startup and new instances:**

  | | FakerPHP | DummyGenerator | DummySlim | Quidam (simple) | Quidam (PHP-DI) |
  |---|---|---|---|---|---|
  | startup (fresh process, first `name()`) | 5.6 ms | 8.1 ms | 2.8 ms | **3.1 ms** | 8.9 ms |
  | new instance (`create`) | 20.3 µs | 26.2 µs | 0.3 µs | **1.2 µs** | 5.4 µs |
  | new instance + first value | 30.8 µs | 194.4 µs | 5.7 µs | **7.5 µs** | 25.4 µs |
  | peak memory at startup | 3.0 MB | 3.0 MB | 2.1 MB | **2.1 MB** | 3.4 MB |
  | peak memory, whole micro run | 11.8 MB* | 5.5 MB | 11.3 MB* | **2.7 MB** | 6.5 MB |

  \* FakerPHP and DummySlim runs include `realText()` (its text model).

  Without PHP-DI Quidam starts almost like DummySlim: PHP-DI's ~6 ms is loading 41 files and building the first container, and its 25 µs first value is autowiring by reflection in every new container. The built-in container reflects too, but with much less machinery. The remaining gap to DummySlim (1.2 vs 0.3 µs per instance) is building the definition array from `DefinitionPack` on every `create()`.
* **Typical operations, all five** (default mode, median per call):

  | | FakerPHP | DummyGenerator | DummySlim | Quidam (simple) | Quidam (PHP-DI) |
  |---|---|---|---|---|---|
  | `name()` | 4.21 µs | 18.21 µs | 636 ns | 631 ns | 651 ns |
  | `email()` | 11.56 µs | 28.19 µs | 1.25 µs | 1.24 µs | 1.28 µs |
  | `address()` | 15.87 µs | 47.80 µs | 3.38 µs | 3.38 µs | 3.45 µs |
  | `text(200)` | 30.10 µs | 6.06 ms | 12.27 µs | 12.42 µs | 12.38 µs |
  | `iban()` | 5.95 µs | 50.94 µs | 5.74 µs | 5.82 µs | 5.89 µs |
  | 8-field user record | 66.59 µs | 128.04 µs | 20.91 µs | 21.02 µs | 21.13 µs |
  | unique email (1000 per scope) | 18.62 µs | stalls (40 in 10 s) | 4.50 µs | 5.10 µs | 5.04 µs |

* Compared with **full DummyGenerator** (default mode): names and emails 22-29× faster, lorem text ~500× faster, `url()` ~350× faster, a typical 8-field record **21 µs instead of 128 µs**; `unique()` works (5 µs per email) where DummyGenerator stalls at 40-50 emails in 10 s.
* Compared with **FakerPHP**: 2-9× faster for names, emails, addresses and text; equal for IBAN and card numbers.
* `unique email` is ~12% slower than DummySlim on both containers (5.0-5.1 vs 4.5 µs) - the chain builds `CompositeStrategy` and closures per call (DummyGenerator compatible strategy signature), not a container cost.

**Which container:** PHP-DI when you need its features (helpers like `DI\autowire()->constructorParameter()`, integration with an app's PHP-DI setup). The built-in one when instances are created often - one per test in a big suite saves ~18 µs per instance and ~6 ms per process.

## Environment and method

AMD Ryzen 7 5800H, process pinned to one core, PHP 8.5.10 CLI, OPcache off, no JIT, no Xdebug; FakerPHP main after v1.23.0, DummyGenerator v0.3.0 (working copy, PHP-DI 7.1.1), DummySlim and Quidam working copies. Every library runs in separate processes; per-operation numbers are medians of process medians (3 processes × 5 rounds of ~100 ms); startup is median of 9 fresh processes. Default mode is unseeded, seeded mode uses seed 2026.

Quidam is measured the way people would write it - `$g->ext(XExtensionInterface::class)->method()` on every call, no extension handles kept. `getExtension` measures only fetching an extension (DummySlim `$d->person()`, Quidam `$g->ext(PersonExtensionInterface::class)`). Quidam has no `template` (no `parse()`) and no `realText` (DummySlim add-on).

Optimizations made while benchmarking PHP-DI: `create()` builds the container once (was twice: defaults, then seed and clock), and autowire definitions are reused between containers - `create` went from 17 µs to 5.4 µs.

## Results

Lower is better. `fastest` - within 5% of the fastest; `(×N)` - N times slower than the fastest.

### Startup - first instance in a fresh process (autoload + create + first name())

| | FakerPHP | DummyGenerator | DummySlim | Quidam (PHP-DI) | Quidam (simple) |
|---|---|---|---|---|---|
| default | 5.56 ms (×2.0) | 8.05 ms (×2.9) | 2.78 ms **fastest** | 8.92 ms (×3.2) | 3.11 ms (×1.1) |
| seeded | 5.61 ms (×2.0) | 8.07 ms (×2.9) | 2.77 ms **fastest** | 8.91 ms (×3.2) | 3.13 ms (×1.1) |
| peak memory | 3.0 MB | 3.0 MB | 2.1 MB | 3.4 MB | 2.1 MB |

### Per operation - default mode (median time per call, lower is better)

| | FakerPHP | DummyGenerator | DummySlim | Quidam (PHP-DI) | Quidam (simple) |
|---|---|---|---|---|---|
| `(loop overhead)` | 10 ns **fastest** | 10 ns **fastest** | 12 ns (×1.2) | 11 ns (×1.1) | 10 ns **fastest** |
| `create` | 20.30 µs (×68.6) | 26.24 µs (×88.6) | 296 ns **fastest** | 5.35 µs (×18.1) | 1.23 µs (×4.2) |
| `create+name` | 30.80 µs (×5.4) | 194.35 µs (×34.3) | 5.67 µs **fastest** | 25.44 µs (×4.5) | 7.47 µs (×1.3) |
| `name` | 4.21 µs (×6.7) | 18.21 µs (×28.9) | 636 ns **fastest** | 651 ns **fastest** | 631 ns **fastest** |
| `firstName` | 2.57 µs (×6.8) | 9.88 µs (×26.2) | 383 ns **fastest** | 387 ns **fastest** | 377 ns **fastest** |
| `lastName` | 867 ns (×3.4) | 7.70 µs (×29.9) | 262 ns **fastest** | 264 ns **fastest** | 258 ns **fastest** |
| `email` | 11.56 µs (×9.3) | 28.19 µs (×22.7) | 1.25 µs **fastest** | 1.28 µs **fastest** | 1.24 µs **fastest** |
| `userName` | 7.41 µs (×7.1) | 18.15 µs (×17.5) | 1.04 µs **fastest** | 1.06 µs **fastest** | 1.05 µs **fastest** |
| `url` | 9.73 µs (×5.1) | 660.03 µs (×347.1) | 1.92 µs **fastest** | 1.95 µs **fastest** | 1.90 µs **fastest** |
| `password` | 2.05 µs (×1.4) | 9.27 µs (×6.5) | 1.45 µs **fastest** | 1.47 µs **fastest** | 1.43 µs **fastest** |
| `address` | 15.87 µs (×4.7) | 47.80 µs (×14.1) | 3.38 µs **fastest** | 3.45 µs **fastest** | 3.38 µs **fastest** |
| `city` | 4.16 µs (×5.3) | 21.53 µs (×27.3) | 788 ns **fastest** | 810 ns **fastest** | 797 ns **fastest** |
| `postcode` | 1.87 µs (×1.9) | 2.98 µs (×2.9) | 1.02 µs **fastest** | 1.04 µs **fastest** | 1.01 µs **fastest** |
| `phoneNumber` | 8.13 µs (×5.0) | 2.04 µs (×1.2) | 1.66 µs **fastest** | 1.69 µs **fastest** | 1.64 µs **fastest** |
| `company` | 3.22 µs (×4.4) | 9.78 µs (×13.2) | 739 ns **fastest** | 768 ns **fastest** | 744 ns **fastest** |
| `numberBetween` | 246 ns (×1.6) | 840 ns (×5.6) | 167 ns (×1.1) | 169 ns (×1.1) | 151 ns **fastest** |
| `randomElement` | 588 ns (×2.3) | 4.81 µs (×19.2) | 251 ns **fastest** | 270 ns (×1.1) | 260 ns **fastest** |
| `boolean` | 222 ns (×1.3) | 802 ns (×4.9) | 183 ns (×1.1) | 181 ns (×1.1) | 165 ns **fastest** |
| `dateTimeBetween` | 6.48 µs (×1.3) | 7.27 µs (×1.4) | 5.12 µs **fastest** | 5.15 µs **fastest** | 5.12 µs **fastest** |
| `uuid` | 1.38 µs (×3.0) | 1.68 µs (×3.6) | 465 ns **fastest** | 488 ns (×1.1) | 464 ns **fastest** |
| `sentence` | 3.90 µs (×2.8) | 791.48 µs (×563.0) | 1.41 µs **fastest** | 1.44 µs **fastest** | 1.42 µs **fastest** |
| `paragraph` | 13.55 µs (×2.8) | 2.81 ms (×573.4) | 4.95 µs **fastest** | 4.95 µs **fastest** | 4.91 µs **fastest** |
| `text200` | 30.10 µs (×2.5) | 6.06 ms (×493.7) | 12.27 µs **fastest** | 12.38 µs **fastest** | 12.42 µs **fastest** |
| `iban` | 5.95 µs **fastest** | 50.94 µs (×8.9) | 5.74 µs **fastest** | 5.89 µs **fastest** | 5.82 µs **fastest** |
| `creditCardNumber` | 3.72 µs **fastest** | 13.68 µs (×3.7) | 3.68 µs **fastest** | 3.71 µs **fastest** | 3.73 µs **fastest** |
| `optionalEmail` | 6.04 µs (×6.6) | 7.18 µs (×7.8) | 922 ns **fastest** | 956 ns **fastest** | 979 ns (×1.1) |
| `template` | 15.50 µs (×2.6) | 47.64 µs (×8.1) | 5.90 µs **fastest** | n/a | n/a |
| `userRecord` | 66.59 µs (×3.2) | 128.04 µs (×6.1) | 20.91 µs **fastest** | 21.13 µs **fastest** | 21.02 µs **fastest** |
| `realText` | 77.80 µs (×1.9) | n/a | 40.36 µs **fastest** | n/a | n/a |
| `userRecordProfile` | n/a | n/a | 25.42 µs **fastest** | 25.66 µs **fastest** | 25.42 µs **fastest** |
| `getExtension` | n/a | n/a | 107 ns (×1.2) | 101 ns (×1.1) | 91 ns **fastest** |
| `unique email` (1000 per scope) | 18.62 µs (×4.1) | **stalls** - 40 of 1000 in 10 s | 4.50 µs **fastest** | 5.04 µs (×1.1) | 5.10 µs (×1.1) |

### Per operation - seeded mode (median time per call, lower is better)

| | FakerPHP | DummyGenerator | DummySlim | Quidam (PHP-DI) | Quidam (simple) |
|---|---|---|---|---|---|
| `(loop overhead)` | 12 ns (×1.1) | 11 ns (×1.1) | 10 ns **fastest** | 10 ns **fastest** | 11 ns (×1.1) |
| `create` | 22.14 µs (×74.3) | 52.70 µs (×176.9) | 298 ns **fastest** | 5.38 µs (×18.1) | 1.23 µs (×4.1) |
| `create+name` | 31.54 µs (×6.4) | 202.83 µs (×41.0) | 4.95 µs **fastest** | 24.53 µs (×5.0) | 6.87 µs (×1.4) |
| `name` | 4.14 µs (×6.6) | 2.57 µs (×4.1) | 630 ns **fastest** | 656 ns **fastest** | 631 ns **fastest** |
| `firstName` | 2.52 µs (×6.7) | 1.70 µs (×4.5) | 380 ns **fastest** | 390 ns **fastest** | 374 ns **fastest** |
| `lastName` | 857 ns (×3.4) | 425 ns (×1.7) | 260 ns **fastest** | 266 ns **fastest** | 255 ns **fastest** |
| `email` | 11.41 µs (×9.2) | 7.00 µs (×5.6) | 1.24 µs **fastest** | 1.30 µs **fastest** | 1.25 µs **fastest** |
| `userName` | 7.28 µs (×7.0) | 4.45 µs (×4.3) | 1.04 µs **fastest** | 1.07 µs **fastest** | 1.05 µs **fastest** |
| `url` | 9.84 µs (×5.2) | 15.58 µs (×8.2) | 1.92 µs **fastest** | 1.96 µs **fastest** | 1.90 µs **fastest** |
| `password` | 1.93 µs (×1.4) | 1.70 µs (×1.2) | 1.42 µs **fastest** | 1.45 µs **fastest** | 1.45 µs **fastest** |
| `address` | 15.74 µs (×4.7) | 13.52 µs (×4.0) | 3.36 µs **fastest** | 3.47 µs **fastest** | 3.37 µs **fastest** |
| `city` | 4.10 µs (×5.2) | 3.70 µs (×4.7) | 789 ns **fastest** | 820 ns **fastest** | 803 ns **fastest** |
| `postcode` | 1.82 µs (×1.8) | 1.59 µs (×1.6) | 1.01 µs **fastest** | 1.06 µs (×1.1) | 1.01 µs **fastest** |
| `phoneNumber` | 8.02 µs (×6.2) | 1.30 µs **fastest** | 1.66 µs (×1.3) | 1.71 µs (×1.3) | 1.62 µs (×1.3) |
| `company` | 3.17 µs (×4.3) | 2.29 µs (×3.1) | 742 ns **fastest** | 769 ns **fastest** | 740 ns **fastest** |
| `numberBetween` | 238 ns (×1.6) | 259 ns (×1.8) | 167 ns (×1.1) | 171 ns (×1.2) | 148 ns **fastest** |
| `randomElement` | 566 ns (×2.2) | 380 ns (×1.5) | 252 ns **fastest** | 274 ns (×1.1) | 259 ns **fastest** |
| `boolean` | 222 ns (×1.3) | 223 ns (×1.3) | 195 ns (×1.2) | 183 ns (×1.1) | 166 ns **fastest** |
| `dateTimeBetween` | 6.48 µs (×1.3) | 6.68 µs (×1.3) | 5.10 µs **fastest** | 5.11 µs **fastest** | 5.10 µs **fastest** |
| `uuid` | 1.36 µs (×2.9) | 530 ns (×1.1) | 468 ns **fastest** | 512 ns (×1.1) | 466 ns **fastest** |
| `sentence` | 3.85 µs (×2.7) | 15.17 µs (×10.8) | 1.41 µs **fastest** | 1.44 µs **fastest** | 1.40 µs **fastest** |
| `paragraph` | 13.02 µs (×2.6) | 52.72 µs (×10.7) | 4.93 µs **fastest** | 5.10 µs **fastest** | 4.92 µs **fastest** |
| `text200` | 29.06 µs (×2.4) | 117.79 µs (×9.6) | 12.23 µs **fastest** | 12.39 µs **fastest** | 12.26 µs **fastest** |
| `iban` | 5.94 µs **fastest** | 6.74 µs (×1.2) | 5.79 µs **fastest** | 6.00 µs **fastest** | 5.77 µs **fastest** |
| `creditCardNumber` | 3.67 µs (×1.2) | 3.09 µs **fastest** | 3.66 µs (×1.2) | 3.83 µs (×1.2) | 3.70 µs (×1.2) |
| `optionalEmail` | 6.17 µs (×6.7) | 4.44 µs (×4.8) | 923 ns **fastest** | 963 ns **fastest** | 949 ns **fastest** |
| `template` | 15.44 µs (×2.6) | 10.65 µs (×1.8) | 5.94 µs **fastest** | n/a | n/a |
| `userRecord` | 65.27 µs (×3.1) | 44.23 µs (×2.1) | 20.91 µs **fastest** | 21.26 µs **fastest** | 20.87 µs **fastest** |
| `realText` | 77.37 µs (×1.9) | n/a | 40.55 µs **fastest** | n/a | n/a |
| `userRecordProfile` | n/a | n/a | 25.27 µs **fastest** | 25.40 µs **fastest** | 25.28 µs **fastest** |
| `getExtension` | n/a | n/a | 108 ns (×1.2) | 102 ns (×1.1) | 92 ns **fastest** |
| `unique email` (1000 per scope) | 19.47 µs (×4.3) | **stalls** - 50 of 1000 in 10 s | 4.52 µs **fastest** | 4.98 µs (×1.1) | 5.10 µs (×1.1) |

### Memory and cold start of heavier features (default mode)

| | FakerPHP | DummyGenerator | DummySlim | Quidam (PHP-DI) | Quidam (simple) |
|---|---|---|---|---|---|
| 10 000 unique emails | 1.3 MB | n/a | 1.5 MB | 1.4 MB | 1.4 MB |
| first realText() (builds model) | 6.81 ms | n/a | 10.15 ms | n/a | n/a |
| realText model memory | 7.1 MB | n/a | 8.8 MB | n/a | n/a |
| peak memory, whole micro run | 11.8 MB | 5.5 MB | 11.3 MB | 6.5 MB | 2.7 MB |

## Where PHP-DI could still be cheaper

Not done - measured trade-offs for later:

* **compiled container** (`ContainerBuilder::enableCompilation()`) would remove reflection on the first use of each extension, but writes PHP files to disk and needs a cache directory - unusual for a dev library.
* **definition cache** (`enableDefinitionCache()`) needs APCu, which is usually off in CLI.
* keeping one container and cloning resolved instances isn't possible with PHP-DI - entries are singletons per container.

## Re-run 2026-10-11 - neutral locale, built-in container only, add-ons, pl_PL

Measured 2026-10-11. Raw data: [`bench/results/2026-10-11_210619.json`](../bench/results/2026-10-11_210619.json). Same machine and method as above; Quidam now has one container (built-in - PHP-DI was dropped as a dependency), so it's compared with the "Quidam (simple)" column of 2026-10-09. The worker still calls `ext()` on every operation; typed accessors (`$g->person()`) add ~40 ns per call.

### Summary

* **Since 2026-10-09 Quidam does more per value** - neutral locale defaults, transliterated user names and emails, name / address / company templates, a phone and an address in `Profile::person()`. That costs 10-50% on name-based values; numbers, text, dates, IBAN, cards and UUIDs are unchanged:

  | default mode | 2026-10-09 | 2026-10-11 | change | why |
  |---|---|---|---|---|
  | startup (fresh process) | 3.11 ms | 3.53 ms | within noise | DummySlim moved 2.78 → 2.94 ms in the same runs |
  | new instance (`create`) | 1.23 µs | 1.22 µs | = | |
  | `name()` | 631 ns | 841 ns | +33% | `NAME_FORMAT` template (~150 ns), unisex names in the first name pool |
  | `firstName()` | 377 ns | 428 ns | +14% | first names of the gender together with unisex ones |
  | `email()` | 1.24 µs | 1.76 µs | +42% | names transliterated to ASCII (~220 ns per name part) |
  | `address()` | 3.38 µs | 4.11 µs | +22% | `ADDRESS_FORMAT` template (~530 ns) |
  | `company()` | 744 ns | 1.00 µs | +34% | `COMPANY_FORMATS` template |
  | `text(200)`, `iban()`, `uuid()`, `dateTimeBetween()` | | | = | |
  | 8-field user record | 21.02 µs | 23.57 µs | +12% | the above |
  | profile record | 25.42 µs | 38.57 µs | +52% | `Profile::person()` now has a phone (~1.6 µs) and a consistent `addressData()` (~6.5 µs) |
  | unique email (1000 per scope) | 5.10 µs | 6.14 µs | +20% | `email()` |

* **Compared with DummySlim** Quidam is now within 1.0-1.5× (was 1.0-1.1×): the same speed for numbers, text, dates, payments and UUIDs, 1.1-1.4× for name-based values, which do the extra work above. Compared with **FakerPHP** it stays 2-9× faster for names, emails, addresses and text, and equal for IBAN and cards; compared with **full DummyGenerator** names and emails are 20-30× faster and lorem text ~500× faster.
* **New: add-ons are measured** - `realText()` (Text add-on) 41 µs per call and 11 ms to build the model, the same as DummySlim; `template` (Template add-on) 7.9 µs vs DummySlim's 6.2 µs. Peak memory of the whole micro run is 11.8 MB instead of 2.7 MB because it now includes the `realText()` model (8.8 MB), as DummySlim's and FakerPHP's always did.
* **Instance creation was unchanged in this run** - `create()` 1.22 µs vs DummySlim's 0.31 µs; the gap was building the definition array from `DefinitionPack` on every `create()`. Improved right after - see [Update: instance creation](#update-instance-creation).
* **A regression found and fixed while measuring**: first names merged the gendered and unisex lists on every call (+290 ns per first name - `name()` was 1.22 µs, `email()` 2.18 µs). The lists are now merged once per class. Seeded output didn't change. The run with the regression is kept in [`bench/results/2026-10-11_210023.json`](../bench/results/2026-10-11_210023.json).

### Results

Lower is better. `fastest` - within 5% of the fastest; `(×N)` - N times slower than the fastest.

#### Startup - first instance in a fresh process (autoload + create + first name())

| | FakerPHP | DummyGenerator | DummySlim | Quidam |
|---|---|---|---|---|
| default | 5.70 ms (×1.9) | 8.15 ms (×2.8) | 2.94 ms **fastest** | 3.53 ms (×1.2) |
| seeded | 5.70 ms (×1.8) | 8.22 ms (×2.6) | 3.13 ms **fastest** | 3.29 ms (×1.1) |
| peak memory | 3.0 MB | 3.0 MB | 2.1 MB | 2.1 MB |

#### Per operation - default mode (median time per call, lower is better)

| | FakerPHP | DummyGenerator | DummySlim | Quidam |
|---|---|---|---|---|
| `(loop overhead)` | 11 ns (×1.1) | 10 ns **fastest** | 10 ns **fastest** | 10 ns **fastest** |
| `create` | 20.83 µs (×67.6) | 26.42 µs (×85.7) | 308 ns **fastest** | 1.22 µs (×4.0) |
| `create+name` | 31.48 µs (×5.3) | 196.81 µs (×33.1) | 5.94 µs **fastest** | 8.26 µs (×1.4) |
| `name` | 4.17 µs (×6.5) | 18.33 µs (×28.3) | 647 ns **fastest** | 841 ns (×1.3) |
| `firstName` | 2.55 µs (×6.6) | 10.11 µs (×26.1) | 388 ns **fastest** | 428 ns (×1.1) |
| `lastName` | 868 ns (×3.3) | 7.76 µs (×29.4) | 271 ns **fastest** | 264 ns **fastest** |
| `email` | 11.60 µs (×9.0) | 28.53 µs (×22.0) | 1.29 µs **fastest** | 1.76 µs (×1.4) |
| `userName` | 7.26 µs (×6.7) | 18.41 µs (×16.9) | 1.09 µs **fastest** | 1.51 µs (×1.4) |
| `url` | 9.64 µs (×4.8) | 648.45 µs (×324.9) | 2.00 µs **fastest** | 2.19 µs (×1.1) |
| `password` | 2.02 µs (×1.4) | 9.34 µs (×6.4) | 1.47 µs **fastest** | 1.45 µs **fastest** |
| `address` | 15.83 µs (×4.6) | 48.57 µs (×14.0) | 3.47 µs **fastest** | 4.11 µs (×1.2) |
| `city` | 4.15 µs (×5.1) | 21.92 µs (×27.1) | 809 ns **fastest** | 818 ns **fastest** |
| `postcode` | 1.86 µs (×1.9) | 2.97 µs (×3.0) | 1.06 µs (×1.1) | 983 ns **fastest** |
| `phoneNumber` | 8.28 µs (×4.9) | 2.07 µs (×1.2) | 1.72 µs **fastest** | 1.70 µs **fastest** |
| `company` | 3.19 µs (×4.2) | 9.85 µs (×13.0) | 760 ns **fastest** | 1.00 µs (×1.3) |
| `numberBetween` | 247 ns (×1.6) | 848 ns (×5.6) | 181 ns (×1.2) | 152 ns **fastest** |
| `randomElement` | 581 ns (×2.3) | 4.86 µs (×19.0) | 256 ns **fastest** | 265 ns **fastest** |
| `boolean` | 221 ns (×1.3) | 815 ns (×4.8) | 188 ns (×1.1) | 170 ns **fastest** |
| `dateTimeBetween` | 6.59 µs (×1.3) | 7.28 µs (×1.4) | 5.16 µs **fastest** | 5.21 µs **fastest** |
| `uuid` | 1.36 µs (×2.9) | 1.70 µs (×3.6) | 480 ns **fastest** | 469 ns **fastest** |
| `sentence` | 3.87 µs (×2.8) | 805.66 µs (×572.2) | 1.45 µs **fastest** | 1.41 µs **fastest** |
| `paragraph` | 13.37 µs (×2.7) | 2.81 ms (×572.0) | 5.05 µs **fastest** | 4.92 µs **fastest** |
| `text200` | 30.14 µs (×2.4) | 6.46 ms (×519.0) | 12.69 µs **fastest** | 12.45 µs **fastest** |
| `iban` | 5.82 µs **fastest** | 51.32 µs (×8.8) | 5.96 µs **fastest** | 5.91 µs **fastest** |
| `creditCardNumber` | 3.69 µs **fastest** | 14.01 µs (×3.8) | 3.79 µs **fastest** | 3.72 µs **fastest** |
| `optionalEmail` | 6.04 µs (×6.4) | 7.18 µs (×7.6) | 947 ns **fastest** | 1.19 µs (×1.3) |
| `template` | 15.71 µs (×2.6) | 48.18 µs (×7.8) | 6.15 µs **fastest** | 7.87 µs (×1.3) |
| `userRecord` | 66.46 µs (×3.1) | 130.04 µs (×6.1) | 21.34 µs **fastest** | 23.57 µs (×1.1) |
| `realText` | 78.45 µs (×1.9) | n/a | 43.98 µs (×1.1) | 41.43 µs **fastest** |
| `userRecordProfile` | n/a | n/a | 25.78 µs **fastest** | 38.57 µs (×1.5) |
| `getExtension` | n/a | n/a | 109 ns (×1.2) | 92 ns **fastest** |
| `unique email` (1000 per scope) | 19.76 µs (×4.3) | **stalls** - 40 of 1000 in 10 s | 4.57 µs **fastest** | 6.14 µs (×1.3) |

#### Per operation - seeded mode (median time per call, lower is better)

| | FakerPHP | DummyGenerator | DummySlim | Quidam |
|---|---|---|---|---|
| `(loop overhead)` | 11 ns (×1.1) | 10 ns **fastest** | 11 ns **fastest** | 10 ns **fastest** |
| `create` | 22.40 µs (×71.4) | 52.31 µs (×166.7) | 314 ns **fastest** | 1.25 µs (×4.0) |
| `create+name` | 31.97 µs (×6.4) | 203.29 µs (×40.6) | 5.01 µs **fastest** | 7.35 µs (×1.5) |
| `name` | 4.19 µs (×6.6) | 2.62 µs (×4.1) | 633 ns **fastest** | 842 ns (×1.3) |
| `firstName` | 2.58 µs (×6.8) | 1.73 µs (×4.5) | 381 ns **fastest** | 433 ns (×1.1) |
| `lastName` | 847 ns (×3.2) | 426 ns (×1.6) | 266 ns **fastest** | 266 ns **fastest** |
| `email` | 11.39 µs (×9.1) | 7.06 µs (×5.6) | 1.26 µs **fastest** | 1.78 µs (×1.4) |
| `userName` | 7.30 µs (×6.9) | 4.47 µs (×4.2) | 1.06 µs **fastest** | 1.52 µs (×1.4) |
| `url` | 9.73 µs (×5.0) | 15.69 µs (×8.1) | 1.94 µs **fastest** | 2.21 µs (×1.1) |
| `password` | 1.96 µs (×1.4) | 1.70 µs (×1.2) | 1.45 µs **fastest** | 1.44 µs **fastest** |
| `address` | 15.61 µs (×4.6) | 13.76 µs (×4.0) | 3.41 µs **fastest** | 4.17 µs (×1.2) |
| `city` | 4.08 µs (×5.0) | 3.66 µs (×4.5) | 818 ns **fastest** | 811 ns **fastest** |
| `postcode` | 1.81 µs (×1.9) | 1.60 µs (×1.7) | 1.05 µs (×1.1) | 970 ns **fastest** |
| `phoneNumber` | 8.14 µs (×6.2) | 1.32 µs **fastest** | 1.69 µs (×1.3) | 1.67 µs (×1.3) |
| `company` | 3.17 µs (×4.1) | 2.27 µs (×3.0) | 766 ns **fastest** | 998 ns (×1.3) |
| `numberBetween` | 242 ns (×1.5) | 253 ns (×1.6) | 178 ns (×1.1) | 158 ns **fastest** |
| `randomElement` | 567 ns (×2.2) | 370 ns (×1.4) | 260 ns **fastest** | 261 ns **fastest** |
| `boolean` | 218 ns (×1.3) | 218 ns (×1.3) | 192 ns (×1.1) | 167 ns **fastest** |
| `dateTimeBetween` | 6.62 µs (×1.3) | 6.75 µs (×1.3) | 5.22 µs **fastest** | 5.18 µs **fastest** |
| `uuid` | 1.35 µs (×2.8) | 536 ns (×1.1) | 483 ns **fastest** | 474 ns **fastest** |
| `sentence` | 3.80 µs (×2.7) | 15.33 µs (×10.8) | 1.46 µs **fastest** | 1.42 µs **fastest** |
| `paragraph` | 13.05 µs (×2.6) | 53.18 µs (×10.8) | 4.96 µs **fastest** | 4.94 µs **fastest** |
| `text200` | 29.22 µs (×2.4) | 118.41 µs (×9.6) | 12.28 µs **fastest** | 12.48 µs **fastest** |
| `iban` | 5.74 µs **fastest** | 6.77 µs (×1.2) | 5.88 µs **fastest** | 6.12 µs (×1.1) |
| `creditCardNumber` | 3.63 µs (×1.2) | 3.12 µs **fastest** | 3.69 µs (×1.2) | 3.79 µs (×1.2) |
| `optionalEmail` | 6.01 µs (×6.5) | 4.43 µs (×4.8) | 922 ns **fastest** | 1.19 µs (×1.3) |
| `template` | 15.11 µs (×2.5) | 10.68 µs (×1.8) | 6.06 µs **fastest** | 7.91 µs (×1.3) |
| `userRecord` | 65.29 µs (×3.1) | 44.87 µs (×2.1) | 21.26 µs **fastest** | 23.45 µs (×1.1) |
| `realText` | 77.69 µs (×1.9) | n/a | 40.90 µs **fastest** | 45.92 µs (×1.1) |
| `userRecordProfile` | n/a | n/a | 25.65 µs **fastest** | 38.84 µs (×1.5) |
| `getExtension` | n/a | n/a | 110 ns (×1.2) | 91 ns **fastest** |
| `unique email` (1000 per scope) | 19.81 µs (×4.3) | **stalls** - 50 of 1000 in 10 s | 4.62 µs **fastest** | 6.69 µs (×1.4) |

#### Memory and cold start of heavier features (default mode)

| | FakerPHP | DummyGenerator | DummySlim | Quidam |
|---|---|---|---|---|
| 10 000 unique emails | 1.3 MB | n/a | 1.5 MB | 1.6 MB |
| first realText() (builds model) | 6.86 ms | n/a | 11.05 ms | 11.39 ms |
| realText model memory | 7.1 MB | n/a | 8.8 MB | 8.8 MB |
| peak memory, whole micro run | 11.3 MB | 5.5 MB | 11.3 MB | 11.8 MB |

### Update: instance creation

Measured 2026-10-11 after the run above. Raw data: [`bench/results/2026-10-11_211554.json`](../bench/results/2026-10-11_211554.json); other operations stayed within noise of the run above.

* `create()` builds the default definitions once per process instead of on every call - they are class names, static closures and immutable objects, and every container still creates its own instances (randomizer, extensions, unique memory)
* `with*()` and `fork()` copy the definitions once and assign the new ones, instead of spreading all of them into a new array
* `PlPlGenerator::create()` builds the pack's definitions (with its `Locale`) once per process too

| | before | after | DummySlim |
|---|---|---|---|
| `create()` - benchmark, default mode | 1.22 µs | **401 ns** | 313 ns |
| `create()` + first `name()` - benchmark | 8.26 µs | **7.45 µs** | 5.94 µs |
| `withDefinition()` - one call, pinned core | 572 ns | **325 ns** | - |
| `fork('users.42')` - one call, pinned core | 1.24 µs | **993 ns** | - |
| `PlPlGenerator::create()` - one call, pinned core | 1.81 µs | **869 ns** | - |

A new instance per test or per record now costs about as much as in DummySlim. The rest of `fork()` is deriving the seed (a hash of the parent seed and the key) and reading three services from the container. Seeded output didn't change.
