# Filler

`$generator->ext(FillerInterface::class)` - opt-in add-on, interface `Quidam\Definitions\Addon\FillerInterface`, default implementation `Quidam\Addon\Filler`

Creates objects (DTOs, value objects, entities) and arrays filled with dummy data guessed from **names** and **types**. No configuration, no attributes:

```php
use Quidam\Definitions\Addon\FillerInterface;

final class UserDto
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public int $age,
        public Status $status,              // enum
        public AddressDto $address,         // nested object
        public DateTimeImmutable $createdAt,
        public bool $isAdmin,
        public string $role = 'user',
    ) {
    }
}

$filler = $generator->ext(FillerInterface::class);

$user = $filler->make(UserDto::class);
// id: 'c3d1...-uuid', firstName: 'Diana', lastName: 'Norris', email: 'diana.norris@example.com', age: 47,
// status: Status::Banned, address: AddressDto {street: 'Edwards Crescent 12', city: 'Irenebridge', zipCode: '45315', ...},
// createdAt: 2025-03-12 01:17:14, isAdmin: true, role: 'user'

$payload = $filler->array(['id', 'email', 'firstName', 'createdAt', 'isActive']);  // API payloads, arrays
```

## Methods

| Method | Returns |
|---|---|
| `make(string $class, array $overrides = [])` | object of `$class` - constructor parameters and uninitialized public properties filled |
| `array(array $keys, array $overrides = [])` | `array<string, mixed>` - value for every key, guessed by name |
| `withNullChance(int\|float $chance)` | copy of the filler leaving nullable values `null` with given chance |

## How a value is chosen

For every constructor parameter and public property, first match wins:

1. **override** - `make(User::class, ['role' => 'admin'])`; a `Closure` is called for every object: `['email' => fn () => $generator->unique('users.email', fn ($d) => $d->internet()->email())]`
2. **name** - rules for the whole name (`email`, `firstName`, `zip_code`, `phone`, `price`, `createdAt`...), its last word (`publishedAt`, `expiry_date` → date; `userId` → id; `avatarUrl` → URL) and boolean prefixes (`isActive`, `has_children`, `canEdit` → bool). Names are matched case-insensitively and in any style - `firstName`, `first_name`, `First-Name` are the same
3. **default value** - kept when no name rule matches: `string $role = 'user'` stays `'user'`, `array $tags = []` stays empty
4. **type**:

| Type | Value |
|---|---|
| `int`, `float`, `bool` | 1-1000, 0-1000 (2 decimals), random |
| `string`, `mixed`, no type | lorem word |
| `DateTimeImmutable`, `DateTimeInterface`, `DateTime` | random date (clock based) |
| enum (backed or unit) | random case |
| other class | filled recursively (up to 3 levels deep) |
| `array`, `iterable` | `[]` |
| interface, abstract class | not guessed - `null` when nullable, otherwise throws asking for an override |

A name rule result is converted to the declared type when possible (`string $createdAt` gets `'2025-03-12 01:17:14'`, `string $age` gets `'47'`). When it can't be (`int $email`), the type rule is used instead.

### People and places belong together

Person, contact and address fields of one object come from one [`Profile::person()`](../extensions/profile.md):

* person - `name`, `firstName`, `lastName`, `email`, `userName` / `login` / `nickname`, `gender`, `title`, `birthDate`, `age`: the email is the user name, age matches birth date, name follows the language's order
* phone - `phone`, `phoneNumber`, `mobile`, `telephone`, `tel`: the person's mobile number of the [locale](../locale.md) (`landline` gets a landline number)
* address - `address`, `streetAddress` / `address1`, `city` / `town`, `postcode` / `zip` / `zipCode`, `state` / `region` / `province`, `stateCode`, `county` / `district`, `latitude` / `lng`...: parts of one [`addressData()`](../extensions/address.md) record - the address line contains that city and postcode, the region and county belong to it

Every object (also nested ones) and every `array()` call gets its own person - so a nested `AddressDto` has its parts consistent with each other. `gender` fills a `string` property with `'male'` / `'female'`, a `Gender` property with the case, and a property of your own backed enum with the case of the same value.

## Examples

```php
// many objects - with unique emails across all of them
$users = $generator->many(50, fn (Quidam $d) => $d->ext(FillerInterface::class)->make(UserDto::class, [
    'email' => fn () => $d->unique('users.email', fn (Quidam $x) => $x->internet()->email()),
]));

// request payload for an API test
$response = $client->post('/api/users', $filler->array(['firstName', 'lastName', 'email', 'password', 'birthDate']));

// nullable fields sometimes null - 30%
$filler->withNullChance(30)->make(UserDto::class);

// interface parameter - pass it
$filler->make(Notifier::class, ['clock' => new FrozenClock('2026-01-01')]);
```

## Rules

| Rule constant | Matches | Example |
|---|---|---|
| `NAME_RULES` | whole name, lowercase without `_` / `-` | `'zipcode' => 'address.postcode'` |
| `WORD_RULES` | last word of the name | `'at' => "dateTime.dateTimeBetween('-2 years', 'now')"` |
| `BOOLEAN_PREFIXES` | first word of a longer name | `'is'`, `'has'`, `'can'` |

~150 names are covered: person, contact, address, company, internet, text (`title`, `description`, `body`...), money and commerce (`price`, `currency`, `sku`, `quantity`...), identifiers (`id`, `uuid`, `isbn`, `vin`...), files, colors, locales. Rules are [Template](template.md) expressions, or `@key` of the shared profile (`'email' => '@email'`, nested keys with a dot: `'city' => '@address.city'`).

Own rules - extend the class:

```php
use Quidam\Addon\Filler;
use Quidam\Definitions\Addon\FillerInterface;

class ShopFiller extends Filler
{
    /** @var array<string, string> */
    protected const array NAME_RULES = [
        ...parent::NAME_RULES,
        'productcode' => 'commerce.sku',
        'nip' => "strings.numerify('##########')",
    ];
}

$generator->ext(ShopFiller::class)->make(Order::class);

// or for everyone using FillerInterface
$generator = $generator->withDefinition(FillerInterface::class, ShopFiller::class);
```

## Notes

* only **public** state is filled: constructor parameters (also promoted / readonly) and public typed properties without default value. Private / protected properties (i.e. Doctrine `private ?int $id`) are never touched
* name rules beat default values - `?DateTimeImmutable $deletedAt = null` gets a date (`*At` rule); pass `['deletedAt' => null]` to keep it empty
* `make()` throws on unknown override names - typos don't pass silently
* nested objects stop at depth 3 - a nullable self reference (`?Node $parent`) ends with `null`, a required one (`Node $next`) can't be built and throws
* values come from the Quidam randomizer - with seed, the same objects are created on every run
* for a fixed shape with exact control use [`many()`](../quidam.md#many-values-and-sequences) with explicit generators; the filler is for "realistic enough" objects with no code
