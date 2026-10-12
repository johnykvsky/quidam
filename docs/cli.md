# Command line

`vendor/bin/quidam` generates values from the terminal - for quick test data, seeding a database from a shell script, or CSV / JSON files for imports. It runs on the [Template](addons/template.md) add-on, so it uses the same aliases.

```text
$ vendor/bin/quidam internet.email -n 3 --seed 2026
brenda_patterson@example.org
bill154@example.com
mary719@example.org

$ vendor/bin/quidam -t '{{ person.name }} <{{ internet.email }}> {{ phoneNumber.internationalPhoneNumber }}' -n 2 -s 2026
Brenda Patterson <lily_patterson@example.com> +94 815 408 939
Olivia Cook <jstevens01@example.com> +62 787 246 318

$ vendor/bin/quidam 'payment.localPrice(10, 50)' -n 2 -s 2026
35.83 EUR
44.77 EUR

$ vendor/bin/quidam 'text.realText(60, 120)' -s 2026
The two species that had stopped me on the lever, and I felt then.

$ vendor/bin/quidam profile.person -f json -s 2026 --now '2026-01-15 12:00' --timezone UTC
{
    "gender": "female",
    "title": "Miss",
    "firstName": "Brenda",
    "lastName": "Patterson",
    "name": "Brenda Patterson",
    "userName": "brenda.patterson",
    "email": "brenda.patterson@example.net",
    "dateOfBirth": "1953-09-23T00:00:00+00:00",
    "age": 72,
    "phone": "07931 540893",
    "address": {
        "street": "Olivia Drive 1",
        "city": "Bellaville",
        ...
        "address": "Olivia Drive 1, 63181 Bellaville"
    }
}
```

## Usage

```text
quidam <alias.method[(arguments)]> [options]
quidam -t '<template>' [options]
quidam --list [alias]
```

**Expression** - `alias.method` or `alias.method(arguments)`, the same syntax as [Template](addons/template.md) tokens, without braces. Aliases are the extension names (`person`, `internet`, `payment`...) and the text add-ons (`text`, `richText`, `edgeCases`) - `--list` shows them. Arguments are PHP literals - quote the expression in the shell:

```text
quidam 'number.numberBetween(1, 100)'
quidam "dateTime.date('d.m.Y')"
quidam 'payment.iban(countryCode: "PL", formatted: true)'
```

**Template** (`-t`) - text with `{{ alias.method }}` tokens, i.e. a line of a SQL file or a log entry.

## Options

| Option | Meaning |
|---|---|
| `-n`, `--count <n>` | number of values, default 1 |
| `-s`, `--seed <int>` | seed - the same seed (and `--now`) gives the same values on every run |
| `-u`, `--unique` | all values different from each other (fails when not enough unique values exist) |
| `-t`, `--template <text>` | template instead of one expression |
| `-f`, `--format <format>` | `text` (default), `json` or `csv` |
| `--now <date>` | frozen "now" - for relative dates, birth dates, `uuid7` / `ulid` / `snowflake` |
| `--timezone <tz>` | timezone of generated dates, default PHP's default timezone |
| `-l`, `--language <pack>` | language pack - a code of a pack in `Quidam\Language` (`pl_PL`) or a `ProviderPackInterface` class name (needs a constructor without arguments) |
| `-c`, `--config <file>` | PHP file returning a factory of the generator - see [Config file](#config-file); without it the default `Quidam::create()` is used |
| `--list [alias]` | list aliases, or methods of one alias with their parameters - with `-l` / `-c`, of the classes actually used |
| `-h`, `--help` | usage |

Both `--count 5` and `--count=5` work, short options also as `-n5`.

## Config file

For your own extensions, packs whose constructor takes arguments, or any `withDefinition()`, give a PHP file with `--config` (`-c`). It returns a factory - not a generator - so `--seed` and `--now` still apply:

```php
// quidam.php
use Quidam\Addon\Template;
use Quidam\Quidam;
use Psr\Clock\ClockInterface;

return static fn (?int $seed, ClockInterface $clock): Quidam => Quidam::create($seed, $clock)
    ->withProvider(new DeProviderPack(region: 'BY'))
    ->withDefinition(Car::class, static fn (RandomizerInterface $r) => new Car($r, prefix: 'Auto'))
    // own alias for expressions and templates: car.brand, {{ car.brand }}
    ->withDefinition(Template::alias('car'), Car::class);
```

```text
quidam -c quidam.php car.brand -n 3 --seed 1
quidam -c quidam.php -t '{{ car.brand }} of {{ person.name }}'
```

The path is explicit - the CLI never picks up a config by itself, so without `-c` you always get default Quidam. `-l` is applied on top of the configured generator; aliases add up - `-c quidam.php -l pl_PL` has `car` and `nationalId`.

## Output formats

| Format | Output |
|---|---|
| `text` | one value per line; arrays as one-line JSON, `true` / `false`, empty line for `null`, dates as ISO 8601 |
| `json` | pretty printed - the value for `-n 1`, a list for more |
| `csv` | header and rows - arrays of the same shape (`profile.person`, `address.countryData`...) become columns, nested arrays (the profile's `address`) a JSON cell, scalars a `value` column |

Dates are ISO 8601 (`2026-01-15T12:00:00+00:00`), enums their value (or name for unit enums).

## Examples

```text
# 1000 unique emails for an import file
quidam internet.email -n 1000 --unique > emails.txt

# people as CSV for a spreadsheet / import test
quidam profile.person -n 500 -f csv > people.csv

# SQL inserts
quidam -t "INSERT INTO users (name, email) VALUES ('{{ person.name }}', '{{ internet.email }}');" -n 100 > users.sql

# the same data every time (CI, docs, bug reports)
quidam 'payment.iban("PL")' -n 3 --seed 42

# Polish data - the included pl_PL pack, its own methods and the nationalId alias
quidam -l pl_PL profile.citizen -f json
quidam -l pl_PL -t '{{ person.name }};{{ nationalId.pesel }};{{ payment.iban }}' -n 100 > clients.csv
quidam -l pl_PL phoneNumber.tollFreePhoneNumber

# your own pack
quidam -l 'App\Faker\DeProviderPack' person.name

# what can I generate?
quidam --list
quidam --list payment
quidam -l pl_PL --list phoneNumber
```

## Errors

Unknown aliases, methods, bad arguments and options print `Error: ...` to stderr. Exit codes: `0` success, `1` error while generating (unknown method, invalid argument, not enough unique values), `2` invalid command line.

## Notes

* aliases are those of the `TemplateInterface` in the container - built-in extensions and text add-ons (`Template::GENERATORS`) plus aliases registered with `Template::alias()` - by a language pack (pl_PL adds `nationalId`) or your [config file](#config-file)
* the SQL template example doesn't escape values - fine for built-in names and emails (they contain no quotes), but your own data lists may - escape or use your database's import tools for anything else
* the command is `src/Cli/Application.php` - `run(array $argv, $stdout, $stderr): int`, usable from your own scripts
