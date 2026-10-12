# DateTime

`$generator->dateTime()` - interface `Quidam\Definitions\Extension\DateTimeExtensionInterface`, default implementation `Quidam\Core\DateTime`

Random dates and times, dates of birth, timezones. All dates are `DateTimeImmutable` in the clock timezone and "now" comes from the [clock](../quidam.md#clock).

## Methods

| Method | Returns | Example |
|---|---|---|
| `dateTime($until = 'now')` | date within 50 years before `$until` | `2002-05-22 19:41:29` |
| `dateTimeBetween($from = '-30 years', $until = 'now')` | date in range | `2020-11-27 10:44:12` |
| `dateTimePast($from = '-30 days')` | date between `$from` and now | `2026-09-13 11:30:16` |
| `dateTimeFuture($until = '+30 days')` | date between now and `$until` | `2027-01-25 18:12:51` |
| `dateTimeRange($from = '-30 years', $until = 'now', $maxDuration = null)` | `array{start, end}` - start <= end | `[2026-10-28 09:02:32, 2026-11-02 17:46:46]` |
| `dateTimesBetween(int $count, $from = '-30 years', $until = 'now')` | `list` - `$count` dates in range, sorted | `[2026-10-04 23:09:03, 2026-10-06 01:33:13, ...]` |
| `dateOfBirth(int $minAge = 18, int $maxAge = 80)` | midnight birth date of a person aged within range today | `2003-03-28 00:00:00` |
| `date(string $format = 'Y-m-d', $until = 'now')` | `string` - formatted `dateTime($until)` | `'1984-11-02'` |
| `time(string $format = 'H:i:s', $until = 'now')` | `string` - formatted `dateTime($until)` | `'17:43:43'` |
| `unixTime($until = 'now')` | `int` - timestamp of `dateTime($until)` | `1052456858` |
| `timezone(?string $countryCode = null)` | `string` - timezone identifier of given country; locale country when null, any when locale has none | `'Asia/Dushanbe'`, Polish pack `'Europe/Warsaw'` |
| `century()` | `string` - Roman numerals, `I` up to the current century (clock) | `'XVII'` |
| `monthName(?DateTimeInterface $date = null)` | `string` - of given or random date | `'October'` |
| `dayOfWeek(?DateTimeInterface $date = null)` | `string` | `'Friday'` |
| `localDate(?DateTimeInterface $date = null)` | `string` - numeric date as written locally | `'2026-10-09'` |
| `localDateTime(?DateTimeInterface $date = null)` | `string` | `'2026-10-09 14:05'` |
| `longDate(?DateTimeInterface $date = null)` | `string` - with month name | `'9 October 2026'` |

Local formats are split like the rest of Quidam:

* **layout** comes from the [locale](../locale.md) - `dateFormat()` (`'Y-m-d'`), `timeFormat()` (`'H:i'`) and `longDateFormat()` (`'{day} {month} {year}'`) - ISO 8601 and 24-hour time by default, `m/d/Y` and `g:i A` in an en_US pack
* **words** are extension constants - `MONTHS`, `MONTHS_IN_DATES` (inflected names for long dates, i.e. Polish "października"), `DAYS`

A Polish pack gives `'09.10.2026'` and `'9 października 2026'`. Pass a date to format a value you already have: `longDate($order->createdAt)`.

## Date arguments

Date parameters accept `DateTimeInterface|string|int`:

| Value | Meaning | Example |
|---|---|---|
| `DateTimeInterface` | as is | `new DateTimeImmutable('2020-01-01')` |
| `int` | unix timestamp | `1733785884` |
| absolute date string | date-only means midnight, timezone in string is respected | `'2020-01-01'`, `'2020-01-01 10:00 UTC'`, `'@1733785884'` |
| relative date string | relative to clock "now" | `'-30 days'`, `'+1 year'`, `'next monday'`, `'tomorrow'` |

Numeric strings (`'2020'`, `'1733785884'`) throw - they are ambiguous, use `int` or `'@1733785884'` for timestamps.

## Examples

```php
$dateTime = $generator->dateTime();

$dateTime->dateTimeBetween('2020-01-01', '2020-12-31');     // 2020-11-27 10:44:12
$dateTime->dateTimeBetween('-1 week', '+1 week');            // around now
$dateTime->dateTimeFuture('+1 year');                        // 2027-01-25 18:12:51
$dateTime->date('d.m.Y', '2000-01-01');                      // '06.05.1990'

// users table
$user = [
    'name' => $generator->person()->name(),
    'dateOfBirth' => $dateTime->dateOfBirth()->format('Y-m-d'),      // adult, 18-80
    'createdAt' => $dateTime->dateTimePast('-2 years'),
];

$child = $dateTime->dateOfBirth(0, 17);                      // 2012-02-10
$dateTime->timezone('US');                                   // 'America/Kentucky/Louisville'
```

Ranges and series:

```php
// booking: starts within a year, lasts at most 14 days
[$checkIn, $checkOut] = $dateTime->dateTimeRange('now', '+1 year', '14 days');

// subscription period within the last 2 years
[$start, $end] = $dateTime->dateTimeRange('-2 years', 'now');

// order history - 5 dates, oldest first
foreach ($dateTime->dateTimesBetween(5, '-1 year', 'now') as $orderedAt) {
    $orders[] = ['orderedAt' => $orderedAt, 'total' => $generator->payment()->price(10, 500)];
}
```

`$maxDuration` is a `DateInterval` or a relative string (`'14 days'`, `'2 hours'`, `'1 month'`). Without it, start and end are two random points of the range; with it, start is random and end is at most `$maxDuration` later (and never after `$until`).

Frozen clock makes dates repeatable:

```php
use Quidam\Clock\FrozenClock;

$fixed = Quidam::create(seed: 1, clock: new FrozenClock('2026-10-08 12:00:00 Europe/Warsaw'))->dateTime();
$fixed->dateTimePast('-1 day');    // always between 2026-10-07 12:00 and 2026-10-08 12:00
```

## Performance - many dates

Strings are convenient and fast enough for most uses (~5 µs per date, faster than Faker): absolute ones (`'2020-01-01'`) are parsed once and remembered, relative ones (`'-1 year'`) are resolved against the clock "now" on every call - "now" moves. For large loops (seeders, fixtures with thousands of rows) pass date objects - nothing is parsed (~4 µs):

```php
use Quidam\Clock\SystemClock;

$clock = new SystemClock();                     // the clock given to Quidam::create()
$generator = Quidam::create(clock: $clock);

$now = $clock->now();
$from = $now->modify('-1 year');

foreach ($orders as $i => $order) {
    $orders[$i]['createdAt'] = $generator->dateTime()->dateTimeBetween($from, $now);
}
```

Make the objects **from the same clock** - `new DateTimeImmutable('-1 year')` uses the system time, so a frozen clock (and seeded, repeatable dates) would stop applying to these values. `dateTimesBetween($count, $from, $until)` does the same for a sorted list - it resolves the arguments once.

## Notes

* result timezone is the clock timezone - use `SystemClock('Europe/Warsaw')` or call `->setTimezone()` on result
* `dateOfBirth()`: a person born on February 29 has birthday on March 1 in non-leap years; `0 <= minAge <= maxAge`
* `dateTimeBetween()`, `dateTimeRange()` and `dateTimesBetween()` throw when `$from` is after `$until`; `dateTimeRange()` throws for negative or invalid `$maxDuration`
* `dateTimesBetween()` dates can repeat (independent draws) - wrap in `unique()` per value if they must differ
* resolution is one second
