# Edge cases

`$generator->ext(EdgeCasesInterface::class)` - opt-in add-on, interface `Quidam\Definitions\Addon\EdgeCasesInterface`, default implementation `Quidam\Addon\EdgeCases`

Strings which often break code: blank and invisible characters, unicode tricks, right-to-left text, emoji, number lookalikes, reserved words and harmless injection probes. For negative tests of validation, escaping, encoding, sorting, search and length limits.

```php
use Quidam\Definitions\Addon\EdgeCasesInterface;

$edge = $generator->ext(EdgeCasesInterface::class);

$edge->string();                    // random one of all, i.e. 'Straße'
$edge->string('injection');         // '@SUM(1+1)'
$edge->strings('emoji');            // all strings of the category
$edge->longString(256);             // 256 ASCII letters
$edge->longString(256, true);       // 256 multibyte characters (2-4 bytes each)
```

## Methods

| Method | Returns |
|---|---|
| `string(?string $category = null)` | `string` - random edge case from category, or from all |
| `strings(?string $category = null)` | `list<string>` - all edge cases of category, or all |
| `categories()` | `list<string>` - category names |
| `longString(int $length = 10000, bool $multibyte = false)` | `string` - exactly `$length` characters |

## Categories

| Category | What's inside |
|---|---|
| `blank` | empty string, spaces, tab, newlines, no-break and other unicode spaces, leading / trailing spaces |
| `reserved` | `null`, `NULL`, `undefined`, `true`, `false`, `N/A`, `[object Object]`, Windows device names (`CON`, `NUL`), `.`, `..` |
| `numeric` | `-1`, `00123`, `1e309`, `NaN`, `Infinity`, `0x1F`, `1,000.00` / `1.000,00`, `½`, Arabic digit `٣`, values just over 64-bit int range |
| `unicode` | combining accents, `Straße`, Turkish `İı`, ligatures, fullwidth, 4-byte characters, CJK, Korean, symbols, Zalgo |
| `rtl` | Arabic, Hebrew, mixed direction, right-to-left override (`exe.png` trick), RTL mark |
| `invisible` | zero-width space / joiner, BOM, soft hyphen, word joiner, NUL byte, bell, ANSI escape codes |
| `emoji` | single, skin tone modifier, ZWJ family, flag, variation selector, keycap, emoji in text |
| `injection` | SQL (`' OR '1'='1`), HTML / JS (`<script>alert(1)</script>`), template (`{{7*7}}`, `${7*7}`), path traversal, CRLF header, CSV formula (`=1+1`), LDAP, shell, format string, XML entity |

Injection strings are **probes**, not attacks - `alert(1)`, `{{7*7}}`, `$(echo injected)` only show whether input is escaped (i.e. `49` in output means a template got evaluated).

## Examples

All edge cases as a PHPUnit data provider:

```php
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Quidam;
use PHPUnit\Framework\Attributes\DataProvider;

/** @return iterable<string, array{string}> */
public static function edgeCases(): iterable
{
    foreach (Quidam::create()->ext(EdgeCasesInterface::class)->strings() as $i => $string) {
        yield '#' . $i . ' ' . json_encode($string) => [$string];
    }
}

#[DataProvider('edgeCases')]
public function testCommentIsEscaped(string $text): void
{
    $html = $this->render(['comment' => $text]);

    self::assertStringNotContainsString('<script>', $html);
}
```

Mixed into normal data - an edge case in 10% of records:

```php
$name = $generator->optional(fn (Quidam $d) => $d->ext(EdgeCasesInterface::class)->string(), 10)
    ?? $generator->person()->name();
```

## Own cases

Cases are `protected const array CASES` - extend the class to add categories or strings:

```php
class AppEdgeCases extends EdgeCases
{
    /** @var array<string, list<string>> */
    protected const array CASES = [
        ...parent::CASES,
        'sku' => ['SKU-', 'sku-0001', 'SKU-' . PHP_INT_MAX],
    ];
}

$generator->ext(AppEdgeCases::class)->string('sku');
// or replace the built-in one everywhere
$generator = $generator->withDefinition(EdgeCasesInterface::class, AppEdgeCases::class);
```

## Notes

* every string is valid UTF-8 (`NUL` byte and control characters included on purpose - they're valid UTF-8 too)
* `string()` is seeded like everything else - with seed the same edge cases come in the same order
* unknown category throws `InvalidArgumentException` listing available ones
