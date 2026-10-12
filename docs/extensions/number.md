# Number

`$generator->number()` - interface `Quidam\Definitions\Extension\NumberExtensionInterface`, default implementation `Quidam\Core\Number`

Integers, digits, floats, normally distributed numbers and booleans.

## Methods

| Method | Returns | Example |
|---|---|---|
| `numberBetween(int $min = 0, int $max = PHP_INT_MAX)` | `int` | `numberBetween(1, 10)` → `5` |
| `randomDigit()` | `int` - 0-9 | `7` |
| `randomDigitNotZero()` | `int` - 1-9 | `1` |
| `randomNumber(?int $nbDigits = null, bool $strict = false)` | `int` - up to `$nbDigits` digits (exactly with `$strict`) | `74379487`, `randomNumber(5, true)` → `63375` |
| `randomFloat(int $nbMaxDecimals = 2, float $min = 0.0, float $max = 1000.0)` | `float` | `264.12`, `randomFloat(1, 10, 20)` → `17.0` |
| `gaussian(float $mean = 0.0, float $stdDev = 1.0, ?float $min = null, ?float $max = null, int $nbMaxDecimals = 2)` | `float` - normal distribution | `gaussian(170, 10, 140, 210, 1)` → `174.2` |
| `gaussianInt(float $mean = 0.0, float $stdDev = 1.0, ?int $min = null, ?int $max = null)` | `int` - normal distribution | `gaussianInt(38, 12, 18, 90)` → `57` |
| `boolean(int\|float $chanceOfGettingTrue = 50)` | `bool` | `true` |
| `percentage(int $nbMaxDecimals = 0, float $min = 0.0, float $max = 100.0)` | `float` - within 0-100 | `21.0`, `percentage(1, 10, 20)` → `11.0` |

## Examples

```php
$number = $generator->number();

$order = [
    'quantity' => $number->numberBetween(1, 5),
    'discount' => $number->randomFloat(2, 0.0, 0.3),
    'isGift' => $number->boolean(10),               // true in 10% of calls
    'code' => $number->randomNumber(6, true),       // always 6 digits
];
```

## Normal distribution

Uniform numbers look fake for things which cluster around a typical value - heights, ages, prices, durations, scores. `gaussian()` gives the bell curve: most values close to `$mean`, ~68% within one `$stdDev`, ~95% within two:

```php
$number->gaussian(170, 10, 140, 210, 1);        // height in cm: 174.2
$number->gaussianInt(38, 12, 18, 90);           // age of an adult customer: 57
$number->gaussian(49.99, 15, 0.99);             // price, never below 0.99
$number->gaussianInt(5, 2, 1);                  // items in order, at least 1
```

`$min` / `$max` cut the curve - values out of range are drawn again, so the shape inside the range is kept (no pile of values at the limits). When the range is so far from the mean that 1000 draws miss it (`gaussian(0, 1, 50)`), `OverflowException` is thrown.

## Notes

* `randomFloat()` result is always within `[$min, $max]` - every value with given precision is equally likely. Throws when no number with given decimals fits the range (i.e. `randomFloat(0, 0.6, 0.9)`)
* `randomNumber()` without `$nbDigits` uses random 1-9 digits, max is 18 digits
* `boolean()` chance: `int` is a percent (0-100), `float` a probability (0.0-1.0) - `boolean(1)` is 1%, `boolean(1.0)` is 100%. Out of range throws
* `gaussian()` throws `InvalidArgumentException` when `$stdDev < 0`, `$min > $max` or `$nbMaxDecimals < 0`; `$stdDev = 0` always returns `$mean` (rounded)
* for skewed / weighted values see [`random()->weightedElement()`](random.md#weighted-values)
