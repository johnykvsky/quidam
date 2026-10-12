# Template

`$generator->ext(TemplateInterface::class)` - opt-in add-on, interface `Quidam\Definitions\Addon\TemplateInterface`, default implementation `Quidam\Addon\Template`

Replaces `{{ alias.method }}` tokens with generated values. Quidam's core has no `parse()` - typed calls are the API - so string templates live here, used only when you ask for them.

It's useful when a format is **data, not code** - stored in YAML / JSON fixtures, database, config or passed from CLI. In PHP code you don't need it - string interpolation is typed and checked by IDE and static analysis:

```php
"{$generator->person()->firstName()} <{$generator->internet()->email()}>";
```

## Usage

```php
use Quidam\Definitions\Addon\TemplateInterface;

$template = $generator->ext(TemplateInterface::class);

$template->parse('{{ person.firstName }} {{ person.lastName }} <{{ internet.email }}>');
// 'Brenda Patterson <lily_patterson@example.com>'

$template->parse("INV-{{ number.numberBetween(1000, 9999) }}/{{ dateTime.date('Y') }}");
// 'INV-7923/2016'

$template->parse('{{ number.numberBetween(min: 1, max: 10) }} {{ strings.bothify("??-##") }}');
// '7 kq-38'

$template->parse('Paid with {{ payment.creditCardType }} ending {{ strings.numerify("####") }}');
// 'Paid with Visa ending 4821'
```

## Token syntax

```
{{ alias.method }}
{{ alias.method() }}
{{ alias.method(argument, argument) }}
{{ alias.method(name: argument) }}
```

Spaces inside braces are optional (`{{person.name}}` works too).

### Aliases

`alias` is looked up in the generators map - `aliases()` returns it. Default map is `Template::GENERATORS` - every extension, the randomizer and the text add-ons - plus aliases registered in the container (see below):

`address`, `barcode`, `blood`, `color`, `commerce`, `company`, `dateTime`, `enum`, `file`, `hash`, `internet`, `language`, `lorem`, `number`, `payment`, `person`, `phoneNumber`, `profile`, `random`, `strings`, `system`, `userAgent`, `uuid`, `vehicle`, `version`, `text`, `richText`, `edgeCases`

Alias is mapped to a container id (`'person' => PersonExtensionInterface::class`), so `person` gives the same object as `$generator->person()` - including your replacement registered with `withDefinition()` or a language pack.

### Methods

Any public, non-static method of the extension - except magic `__` methods.

### Arguments

Only PHP literals:

| Literal | Example |
|---|---|
| string in single or double quotes, backslash escapes, may contain `,` and `()` | `'Y-m-d'`, `"it's"`, `'it\'s'` |
| integer | `10`, `-5` |
| float | `1.5`, `-0.25` |
| boolean, null | `true`, `false`, `null` |

Positional and named arguments are supported, named ones after positional (like in PHP):

```php
$template->parse('{{ lorem.sentence(4, variableWordCount: false) }}');
$template->parse("{{ dateTime.date('d.m.Y', '2000-01-01') }}");
```

No variables, arrays, constants or nested tokens.

### Raw values - evaluate()

`evaluate()` runs one expression (without braces) and returns the value as the generator returned it - arrays, dates and objects are not converted:

```php
$template->evaluate('number.numberBetween(1, 10)');     // 7 (int)
$template->evaluate('profile.person');                  // array
$template->evaluate("dateTime.dateTimePast('-1 week')"); // DateTimeImmutable
```

It's what the [Filler](filler.md) rules and the [CLI](../cli.md) use.

### Results

Result is converted to string:

| Result | Output |
|---|---|
| string, int, float | as is |
| bool | `true` / `false` |
| null | empty string |
| `DateTimeInterface` | `Y-m-d H:i:s` (use `dateTime.date('format')` for other formats) |
| backed enum | its value |
| unit enum | its name |
| `Stringable` | `__toString()` |
| array | items converted as above, joined by `, ` (i.e. `{{ lorem.words(3) }}` → `'magnam, ut, quia'`) |
| other object | throws |

## Errors

Text which isn't in `alias.method` form (i.e. `{{ firstName }}`, `{{ }}`) is left untouched.

Everything else that is wrong throws `Quidam\Exception\InvalidArgumentException` with the token name - typos are not silently ignored:

| Template | Error |
|---|---|
| `{{ foo.bar }}` | Unknown template generator "foo". Available: address, barcode... |
| `{{ person.nope }}` | Unknown template method "person.nope" |
| `{{ number.numberBetween(1, max) }}` | Unsupported template argument "max" - use quoted string, number, true, false or null |
| `{{ number.numberBetween(min: 1, 5) }}` | Positional argument "5" cannot follow named argument |
| `{{ number.numberBetween('a') }}` | Cannot call "number.numberBetween": ... must be of type int, string given |

## Own generators and restrictions

To add your extension, register it under `Template::alias()` - the container id `template.<alias>`:

```php
use Quidam\Addon\Template;
use Quidam\Definitions\Addon\TemplateInterface;

$generator = $generator->withDefinition(Template::alias('car'), Car::class);

$generator->ext(TemplateInterface::class)->parse('{{ car.brand }} owned by {{ person.name }}');
```

Every alias is its own definition, so aliases add up: your code, a language pack (pl_PL registers `nationalId`) and a [CLI config](../cli.md#config-file) each register theirs without replacing the Template. A string definition points to the entry (`Car::class`, or an interface like `PaymentExtensionInterface::class`) - the same shared instance; a factory or an object works too. Registering a default alias (`Template::alias('person')`) replaces it.

The generators map is also a **whitelist** - templates can call only what's in it. For templates from an untrusted source, give Template an explicit map - then it's exactly these, registered aliases are ignored:

```php
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;

$generator = $generator->withDefinition(TemplateInterface::class, fn (ContainerInterface $c) => new Template($c, [
    'person' => PersonExtensionInterface::class,
    'internet' => InternetExtensionInterface::class,
]));
```

The closure gets the generator's container (`ContainerInterface` is resolved to it), so the map's ids resolve with all your replacements.
