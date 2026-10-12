# Enum

`$generator->enum()` - interface `Quidam\Definitions\Extension\EnumExtensionInterface`, default implementation `Quidam\Core\Enum`

Random case or value of your PHP enum.

## Methods

| Method | Returns |
|---|---|
| `enumCase(string $enum)` | random case of any enum - typed as the enum (`class-string<T>` → `T`) |
| `enumValue(string $enum)` | `int\|string` - value of random case of a backed enum |

## Examples

```php
enum OrderStatus: string
{
    case New = 'new';
    case Paid = 'paid';
    case Shipped = 'shipped';
}

enum Suit
{
    case Hearts;
    case Spades;
}

$generator->enum()->enumCase(OrderStatus::class);   // OrderStatus::Paid
$generator->enum()->enumValue(OrderStatus::class);  // 'shipped'
$generator->enum()->enumCase(Suit::class);          // Suit::Hearts
```

All cases are equally likely. For realistic frequency use `weightedElement()`:

```php
$status = OrderStatus::from($generator->random()->weightedElement(['new' => 20, 'paid' => 30, 'shipped' => 50]));
```

## Notes

* throws when given class isn't an enum, enum has no cases, or `enumValue()` gets a non-backed enum
