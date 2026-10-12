# Blood

`$generator->blood()` - interface `Quidam\Definitions\Extension\BloodExtensionInterface`, default implementation `Quidam\Core\Blood`

ABO blood type with Rh factor, picked with realistic frequency.

## Methods

| Method | Returns | Example |
|---|---|---|
| `bloodType()` | `string` - `A`, `B`, `AB` or `O` | `'O'` |
| `bloodRh()` | `string` - `+` or `-` | `'+'` |
| `bloodGroup()` | `string` - type with Rh factor | `'O+'`, `'AB-'` |

## Examples

```php
$patient = [
    'name' => $generator->person()->name(),
    'dateOfBirth' => $generator->dateTime()->dateOfBirth(0, 90)->format('Y-m-d'),
    'bloodGroup' => $generator->blood()->bloodGroup(),
];
```

## Distribution

| Group | Share | Group | Share |
|---|---|---|---|
| O+ | 38% | O- | 7% |
| A+ | 34% | A- | 6% |
| B+ | 9% | B- | 2% |
| AB+ | 3% | AB- | 1% |

Approximate values (they vary by country). `bloodType()` and `bloodRh()` are derived from the same distribution, so i.e. `O` comes in ~45% and `-` in ~16% of calls.
