# Profile

`$generator->profile()` - interface `Quidam\Definitions\Extension\ProfileExtensionInterface`, default implementation `Quidam\Core\Profile`

Coherent records - values which belong together. Separate calls are independent: `Person::name()` and `Internet::email()` give two different people, and `title()` doesn't know the gender of `firstName()`. Profile generates them as one record - person, contact and address.

## Methods

| Method | Returns |
|---|---|
| `person(?Gender $gender = null, int $minAge = 18, int $maxAge = 80)` | `array` - one person, see below |

`person()` returns:

| Key | Type | Example | Matches |
|---|---|---|---|
| `gender` | `Gender` | `Gender::Female` | given `$gender` or random |
| `title` | `string` | `'Miss'` | gender |
| `firstName` | `string` | `'Brenda'` | gender |
| `lastName` | `string` | `'Patterson'` | gender (languages with gendered surnames) |
| `name` | `string` | `'Brenda Patterson'` | first and last name in the language's order ([`Person::fullName()`](person.md)) |
| `userName` | `string` | `'brenda.patterson'` | built from the name, ASCII (transliterated) |
| `email` | `string` | `'brenda.patterson@example.net'` | the user name in a reserved `example.*` domain |
| `dateOfBirth` | `DateTimeImmutable` | `1953-09-23 00:00:00` | age, relative to clock "now" |
| `age` | `int` | `72` | `$minAge`-`$maxAge` |
| `phone` | `string` | `'07931 540893'` | national mobile number of the [locale](../locale.md) |
| `address` | `array` | `['street' => 'Olivia Drive 1', 'city' => 'Bellaville', ...]` | one [`addressData()`](address.md) record - street, city, region, county, postcode, coordinates and the address line belong together |

(Example generated with `seed: 2026`, clock frozen at `2026-01-15 12:00 UTC`.)

## Examples

```php
use Quidam\Definitions\Enum\Gender;

$person = $generator->profile()->person();

$user = [
    'name' => $person['name'],
    'email' => $person['email'],
    'phone' => $person['phone'],
    'birthDate' => $person['dateOfBirth']->format('Y-m-d'),
    'city' => $person['address']['city'],
    'address' => $person['address']['address'],
];

$generator->profile()->person(Gender::Female, minAge: 65, maxAge: 90);   // senior woman

// a language pack changes all of it consistently - Polish names, +48 mobile numbers, Polish address formats
$generator->withProvider(new PlProviderPack())->profile()->person();
```

Only some parts needed - `Internet` accepts names, so they can match a person you already have:

```php
$first = $generator->person()->firstName();
$last = $generator->person()->lastName();

$generator->internet()->email($first, $last);       // 'anna.kowalski80@example.org' for 'Anna', 'Kowalski'
$generator->internet()->userName($first, $last);    // 'anna.kowalski'
```

The [Filler](../addons/filler.md) add-on fills objects from one profile - name, email, phone and address fields of a DTO belong to the same person and place.

## Notes

* names come from [Person](person.md), user name from [Internet](internet.md), phone from [PhoneNumber](phone-number.md), address from [Address](address.md) - replacing them with `withDefinition()` (i.e. a language pack) changes profiles too
* `gender` parameter is the `Gender` enum (`Gender::Male`, `Gender::Female`) or null for random; the result's `gender` is a `Gender` too
* age is computed against the clock - use a frozen clock for repeatable `age` (see [Clock](../quidam.md#clock))
* the address is fictional by default (region and code match, coordinates random) - see [Address](address.md#notes)
