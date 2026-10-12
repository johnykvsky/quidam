# Strings

`$generator->strings()` - interface `Quidam\Definitions\Extension\StringsExtensionInterface`, default implementation `Quidam\Core\Strings`

Random strings from a character pool, pattern masks and regular expressions.

## Methods

| Method | Returns | Example |
|---|---|---|
| `string(int $min = 3, int $max = 8, string $pool = Strings::ALPHA_POOL)` | `string` - length (in characters) between `$min` and `$max` | `'otvksk'` |
| `numerify(string $string)` | `string` - `#` → digit 0-9, `%` → digit 1-9 | `numerify('ORD-####')` → `'ORD-2749'` |
| `lexify(string $string)` | `string` - `?` → letter a-z | `lexify('id-????')` → `'id-kmgl'` |
| `bothify(string $string)` | `string` - `#` digit, `?` letter, `*` digit or letter | `bothify('??-##-**')` → `'zd-82-2g'` |
| `regexify(string $pattern)` | `string` - matching the regular expression | `regexify('[A-Z]{2}-\d{4}')` → `'EM-2322'` |
| `hexadecimal(int $length = 6, bool $uppercase = false)` | `string` - hex digits | `'0602b9'`, `hexadecimal(8, true)` → `'46DAD838'` |

Pools: `Strings::ALPHA_POOL` (a-z), `Strings::ALPHA_CASE_POOL` (a-z, A-Z), `Strings::ALPHA_NUM_POOL` (0-9, a-z, A-Z) or any string, also with UTF-8 characters.

## Examples

```php
use Quidam\Core\Strings;

$strings = $generator->strings();

$strings->string(10, 10, Strings::ALPHA_NUM_POOL);   // 'xRNDsM4A8e'
$strings->string(6, 6, 'ABCDEF0123456789');          // '5DF5C0' - hex code
$strings->string(5, 5, 'ąęłżź');                     // UTF-8 pool works

$orderNumber = $strings->numerify('ORD-%#####');     // never starts with 0
$plate = strtoupper($strings->bothify('??-#####'));  // 'KX-48213'
$voucher = strtoupper($strings->bothify('****-****-****'));
```

## Regular expressions

`regexify()` generates a string matching the pattern - for formats which masks can't express (alternatives, optional parts, character ranges):

```php
$strings->regexify('[A-Z]{2}-\d{4}');                   // 'EM-2322'
$strings->regexify('(INV|FV)/20(2[4-6])/\d{5}');         // 'FV/2026/56312'
$strings->regexify('PL\d{2}( \d{4}){6}');               // 'PL33 7015 1994 8863 1944 1440 0462'
$strings->regexify('#[0-9a-f]{6}');                      // '#0509cb'
$strings->regexify('[A-HJ-NPR-Z0-9]{17}');               // 'P52W54VWT0GJ1WHYF' - VIN alphabet (no I, O, Q)
```

Pattern is given **without delimiters** and flags. Supported:

| Syntax | Generates |
|---|---|
| `abc`, `ąę`, `\.` `\*` `\(`... | literal characters (UTF-8 too), escaped metacharacters |
| `\n` `\t` `\r` `\xhh` `\x{hhhh}` | control and hex characters |
| `.` | printable ASCII (space to `~`) |
| `[abc]` `[a-z0-9_]` `[^,;]` | character classes, ranges, negation (negated classes pick from printable ASCII) |
| `\d` `\w` `\s` / `\D` `\W` `\S` | digit, word character, space / their printable ASCII complements |
| `(…)` `(?:…)` `(?<name>…)` | groups |
| `a\|b` | alternatives, equally likely |
| `?` `*` `+` `{n}` `{n,}` `{n,m}` `{,m}` | quantifiers - `*`, `+` and `{n,}` repeat at most 10 times more than their minimum |
| `^` `$` `\A` `\z` | anchors - ignored, the whole string is generated |

Backreferences (`\1`), lookarounds (`(?=…)`), word boundaries (`\b`), unicode properties (`\p{L}`), POSIX classes (`[[:alpha:]]`) and inline flags throw `InvalidArgumentException` - they can't be generated reliably. Every character of a class is equally likely, also when listed twice (`[aab]`). Parsed patterns are cached, so repeated calls are cheap.

## Notes

* `%` in `numerify()` is useful for numbers that can't start with 0
* masks replace only `#`, `%`, `?`, `*` - other characters are kept
* letters from masks are lowercase - use `strtoupper()` for uppercase codes
* `string()` throws when `$min < 1`, `$min > $max` or pool is empty
* `regexify()` result always matches `preg_match('/^(?:' . $pattern . ')$/u', ...)` - but "matches" is all it guarantees: `\d{4}-\d{2}-\d{2}` gives `'8051-47-93'`, use `DateTime` for real dates
