# Person

`$generator->person()` - interface `Quidam\Definitions\Extension\PersonExtensionInterface`, default implementation `Quidam\Core\Person`

English names, titles and gender. Other generators (address, company, internet) use it for names.

## Methods

| Method | Returns | Example |
|---|---|---|
| `name(?Gender $gender = null)` | `string` - first and last name of the same gender, `NAME_FORMAT` order | `'Brenda Patterson'` |
| `fullName(string $firstName, string $lastName)` | `string` - given parts in `NAME_FORMAT` order | `fullName('Anna', 'Kowalska')` → `'Anna Kowalska'` |
| `firstName(?Gender $gender = null)` | `string` | `'Keith'` |
| `middleName(?Gender $gender = null)` | `string` - a first name of the same gender | `'Robert'` |
| `lastName()` | `string` | `'Butler'` |
| `suffix()` | `string` | `'Jr.'`, `'III'`, `'PhD'` |
| `age(int $min = 18, int $max = 80)` | `int` - years | `53` |
| `initials(?string $name = null, string $separator = '')` | `string` - uppercase first letters of name parts (random name when null) | `'MS'`, `initials('Anna-Maria Żółkiewska', '.')` → `'A.M.Ż'` |
| `title(?Gender $gender = null)` | `string` | `'Prof.'`, `'Mrs.'` |
| `gender()` | `Gender` - `Gender::Male` or `Gender::Female` (`->value`: `'male'`, `'female'`) | `Gender::Male` |

Gender: `Gender::Male`, `Gender::Female` or `null` (random) - the `Quidam\Definitions\Enum\Gender` enum.

## Examples

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Core\Person;

$person = $generator->person();

$person->name(Gender::Female);   // 'Nicole Stevens'
$person->title(Gender::Male);    // 'Prof.'

// consistent gender
$gender = $person->gender();
$user = [
    'gender' => $gender,
    'title' => $person->title($gender),
    'firstName' => $person->firstName($gender),
    'lastName' => $person->lastName(),
];
```

## Data

* ~100 male and 100 female first names, 150 last names - ~30 000 full names
* 29 unisex first names (`FIRST_NAME_NEUTRAL`: Alex, Jordan, Taylor...) - given to both genders, together with the male and female lists
* titles: male `Mr.`, `Dr.`, `Prof.`; female `Mrs.`, `Ms.`, `Miss`, `Dr.`, `Prof.`
* suffixes: `Jr.`, `Sr.`, `II`, `III`, `IV`, `PhD`
* full name is `NAME_FORMAT` - `'{firstName} {lastName}'`; languages writing the family name first (Hungarian) override it
* `age()` is independent of other values - for an age matching a birth date use [`Profile::person()`](profile.md)
* `initials()` splits on spaces and hyphens and handles multibyte letters (`Łucja` → `Ł`)

## Own names

Name lists are `protected const` - to use names of your language, override them and register the class. All generators using names (address, company, internet) will use yours:

```php
use Quidam\Definitions\Enum\Gender;
use Quidam\Core\Person;
use Quidam\Definitions\Extension\PersonExtensionInterface;

class PlPerson extends Person
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = ['Jan', 'Piotr', 'Krzysztof', 'Andrzej', 'Tomasz'];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = ['Anna', 'Maria', 'Katarzyna', 'Małgorzata', 'Agnieszka'];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];       // English unisex names (Alex, Taylor) don't fit

    /** @var list<string> */
    protected const array LAST_NAME = ['Nowak', 'Kowalski', 'Wiśniewski', 'Wójcik', 'Kowalczyk'];
}

$polish = $generator->withDefinition(PersonExtensionInterface::class, PlPerson::class);
$polish->person()->name();     // 'Anna Kowalczyk'
$polish->company()->company(); // 'Nowak & Wójcik'
```

Overridable: `FIRST_NAME_MALE`, `FIRST_NAME_FEMALE`, `FIRST_NAME_NEUTRAL` (unisex - set it too, or empty, when you replace the others), `LAST_NAME`, `TITLE_MALE`, `TITLE_FEMALE`, `SUFFIX`, `NAME_FORMAT`. See [Extending](../extending.md) for a full language pack. Emails and user names built from non-ASCII names are transliterated automatically - see [Transliterator](transliterator.md).
