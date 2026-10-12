# Random

`$generator->random()` (same as `$generator->ext(RandomizerInterface::class)`) - interface `Quidam\Definitions\Randomizer\RandomizerInterface`

The randomizer used by all extensions, available directly. Default implementation `Quidam\Core\Randomizer\XoshiroRandomizer` wraps PHP `\Random\Randomizer` with Xoshiro256** (seeded, or with a random seed); `Quidam::createSecure()` / `->secure()` switch to `SecureRandomizerInterface` (PHP `Random\Engine\Secure` by default). See [Quidam - Randomizers](../quidam.md#randomizers) for engines and replacing them.

## Methods

| Method | Returns | Example |
|---|---|---|
| `getInt(int $min, int $max)` | `int` | `getInt(1, 6)` → `5` |
| `getFloat(float $min, float $max)` | `float` - both ends included | `getFloat(0.0, 1.0)` → `0.5701858069155191` |
| `getBool(int\|float $chanceOfTrue = 50)` | `bool` | `getBool(30)` → `false` |
| `getBytes(int $length = 16)` | `string` - raw bytes | |
| `getBytesFromString(string $string, int $length = 8)` | `string` - bytes picked from given string | `getBytesFromString('ABC123', 5)` → `'122CB'` |
| `randomLetter()` | `string` - a-z | `'j'` |
| `randomElement(array $array)` | element (typed by array) | `randomElement(['red', 'green', 'blue'])` → `'red'` |
| `randomElements(array $array, int $count = 1, bool $unique = false)` | `list` of elements | `randomElements(['a', 'b', 'c', 'd', 'e'], 3, true)` → `['a', 'd', 'c']` |
| `randomKey(array $array)` | key | `randomKey(['pl' => 'Polish', 'en' => 'English'])` → `'en'` |
| `weightedElement(array $weights)` | key picked by weight | `weightedElement(['new' => 70, 'paid' => 25, 'cancelled' => 5])` → `'new'` |
| `shuffleElements(array $array)` | `list` - shuffled values | `shuffleElements([1, 2, 3, 4, 5])` → `[4, 3, 2, 1, 5]` |

## Examples

```php
$random = $generator->random();

$random->randomElement(['small', 'medium', 'large']);
$random->randomElements(range(1, 49), 6, unique: true);    // lottery numbers
$random->getBool(0.25);                                     // probability 25%
```

## Weighted values

`weightedElement()` returns a key, picked with probability proportional to its weight. Weights are `int` or `float`, don't have to sum to 100, zero weight is never picked:

```php
// order statuses with realistic distribution
$status = $generator->random()->weightedElement(['new' => 20, 'paid' => 30, 'shipped' => 45, 'cancelled' => 5]);

// star ratings skewed to high values
$rating = $generator->random()->weightedElement([5 => 45, 4 => 30, 3 => 12, 2 => 6, 1 => 7]);

// value ranges: mostly small orders, few big ones
$range = $generator->random()->weightedElement(['1-50' => 60, '51-200' => 30, '201-1000' => 10]);
[$min, $max] = array_map('intval', explode('-', $range));
$orderValue = $generator->number()->numberBetween($min, $max);
```

### Skewed numbers

`weightedElement()` covers non-uniform distributions in most cases:

| Need | How |
|---|---|
| few distinct values with different frequency (statuses, ratings, sizes) | `weightedElement()` - exact control of each value |
| skewed number over a small range (1-10) | `weightedElement([1 => 10, 2 => 9, ..., 10 => 1])` |
| skewed number over a wide range (1-10 000) | weighted range buckets + `numberBetween()` (example above) - stepwise, not smooth |
| values around a typical one (bell curve) | [`Number::gaussian()`](number.md#normal-distribution) / `gaussianInt()` |
| other smooth custom distribution curve | not covered - write a small extension |

## Notes

* empty array throws in `randomElement()`, `randomElements()`, `randomKey()`, `weightedElement()`
* `randomElements(..., unique: true)` compares values, throws when array has fewer unique values than requested
* `getInt()` / `getFloat()` throw when `$min > $max`
* `getBool()` chance: `int` is a percent (0-100), `float` a probability (0.0-1.0) - out of range throws
* `weightedElement()` throws for negative weights or when all weights are zero
