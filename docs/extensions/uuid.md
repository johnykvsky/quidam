# Uuid

`$generator->uuid()` - interface `Quidam\Definitions\Extension\UuidExtensionInterface`, default implementation `Quidam\Core\Uuid`

UUID v4, time-ordered UUID v7 and ULID.

## Methods

| Method | Returns | Example |
|---|---|---|
| `uuid4()` | `string` - random UUID (RFC 9562) | `'6b863ade-d5dc-45f1-8705-0a914cdaa92f'` |
| `uuid7(?DateTimeInterface $dateTime = null)` | `string` - time-ordered UUID | `'01a11af4-9900-77d1-a4e8-3e74b4efe323'` |
| `ulid(?DateTimeInterface $dateTime = null)` | `string` - 26 characters, Crockford Base32 | `'01M4DF96807NAG1FW7YXQ5TT01'` |
| `uuid5(string $namespace, string $name)` | `string` - name-based (SHA-1), always the same for the same input | `uuid5(Uuid::NAMESPACE_URL, 'https://example.com/users/42')` → `'38fcaf6d-63bc-5c9f-8a4f-de8cf4e651ed'` |
| `Uuid::NIL`, `Uuid::MAX` | constants - nil and max UUID (RFC 9562) | `'00000000-0000-0000-0000-000000000000'` |
| `uuid3(string $namespace, string $name)` | `string` - name-based (MD5) | `uuid3(Uuid::NAMESPACE_DNS, 'example.com')` → `'9073926b-929f-31c2-abc9-fad77ae3e8eb'` |
| `nanoid(int $size = 21, string $alphabet = Uuid::NANOID_ALPHABET)` | `string` - URL-safe | `'qKteRnqjEI5hXgwHgv8s0'` |
| `snowflake(?DateTimeInterface $dateTime = null, int $epoch = Uuid::SNOWFLAKE_EPOCH)` | `int` - 64-bit, sortable by time | `2108527897607214740` |

## Examples

```php
$uuid = $generator->uuid();

$uuid->uuid4();
$uuid->uuid7();                                         // clock "now"
$uuid->uuid7(new DateTimeImmutable('2020-01-01'));      // '016f5e66-e800-7022-83ae-0fe4d75e6ae8'

// primary keys in creation order
$ids = [];
for ($i = 0; $i < 1000; ++$i) {
    $ids[] = $uuid->uuid7();
}
// $ids are sorted - also when generated in the same millisecond or with frozen clock
```

## Notes

* `uuid7()` without date is **monotonic**: every next value sorts after the previous one. Within the same millisecond (or with a frozen clock) a 12-bit counter is increased, when it overflows the timestamp moves 1 ms forward (RFC 9562 section 6.2). With explicit `$dateTime` the counter part is random
* `ulid()` without date is **monotonic** too, as the ULID spec describes: within the same millisecond the randomness of the previous ULID is increased by 1 (`…88E1F`, `…88E1G`); in the (practically impossible) case its 80 bits overflow, the timestamp moves 1 ms forward. With explicit `$dateTime` the randomness is fresh
* `uuid7()` / `ulid()` time comes from the [clock](../quidam.md#clock) - with frozen clock and seed they are repeatable
* supported dates: 1970-01-01 to 10889-08-02 (48-bit millisecond timestamp), others throw
* `uuid3()` / `uuid5()` are **not random** - the same namespace and name give the same UUID everywhere (RFC 9562). Use them for ids which must be stable across runs and systems: `uuid5(Uuid::NAMESPACE_URL, 'https://example.com/users/' . $id)`. Namespaces: `NAMESPACE_DNS`, `NAMESPACE_URL`, `NAMESPACE_OID`, `NAMESPACE_X500`, or any UUID of your own; invalid namespace throws
* `nanoid()` picks every character uniformly (no modulo bias), custom alphabets work too: `nanoid(10, '0123456789abcdef')`
* `snowflake()` layout: 41 bits of milliseconds since `$epoch`, 10 bits worker id, 12 bits sequence (both random). Default epoch is Twitter's (2010-11-04), Discord uses `1420070400000`. Dates before the epoch or more than 69 years after it throw. Time comes from the clock - with frozen clock and seed the ids are repeatable
