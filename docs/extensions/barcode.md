# Barcode

`$generator->barcode()` - interface `Quidam\Definitions\Extension\BarcodeExtensionInterface`, default implementation `Quidam\Core\Barcode`

Product and publication codes with valid structure and check digit - they pass validation in shops, ERPs and barcode libraries.

## Methods

| Method | Returns | Example |
|---|---|---|
| `ean13()` | `string` - EAN-13 / GTIN-13 with GS1 country prefix | `'8990858761074'` |
| `ean8()` | `string` - EAN-8 / GTIN-8 for small packages | `'76267727'` |
| `upcA()` | `string` - UPC-A (US / Canada) | `'047514328671'` |
| `isbn13()` | `string` - ISBN-13 with valid prefix and registration group | `'9788641908220'` |
| `isbn10()` | `string` - ISBN-10, check digit can be `X` | `'9642826062'` |
| `ismn()` | `string` - ISMN (printed music), 13-digit form | `'9790053820045'` |

## Examples

```php
$barcode = $generator->barcode();

// products table
$product = [
    'name' => $generator->lorem()->sentence(3, false),
    'ean' => $barcode->ean13(),
];

// books table
$book = [
    'title' => $generator->lorem()->sentence(),
    'isbn' => $barcode->isbn13(),
];
```

## Notes

* check digits: GS1 mod 10 for EAN, UPC, ISBN-13 and ISMN (`EanCalculatorInterface`); mod 11 for ISBN-10, `X` means 10 (`IsbnCalculatorInterface`) - replace the calculators for invalid codes in negative tests, see [Calculators](../extending.md#calculators)
* `ean13()` uses real GS1 member organisation (country) prefixes - i.e. `590` Poland, `400-440` Germany, `000-139` US / Canada. Not used: restricted in-store prefixes (`02`, `04`, `2xx`), coupons (`05`, `98x`, `99x`) and publications (`977` ISSN, `978`/`979` ISBN)
* `ean8()` uses prefixes from `300` up - EAN-8 codes starting with `0` or `2` are restricted codes
* `upcA()` number system digit is `0`, `1`, `6`, `7` or `8` (regular products)
* `isbn13()`: 85% `978`, 15% `979` prefix. Registration groups are valid ones (`978-0`... `978-989`, `979-8`, `979-10`... `979-13`) - `979-0` is ISMN, not ISBN
* `isbn10()` uses `978` groups only, `979` ISBNs have no ISBN-10 form
* codes are returned without hyphens - proper ISBN hyphenation needs publisher ranges data
