# Company

`$generator->company()` - interface `Quidam\Definitions\Extension\CompanyExtensionInterface`, default implementation `Quidam\Core\Company`

Company names, job titles, industries and catch phrases.

## Methods

| Method | Returns | Example |
|---|---|---|
| `company()` | `string` - `COMPANY_FORMATS` | `'Hunter & Patterson'`, `'Young-Patterson Group'`, `'Brown Capital Co'` |
| `companySuffix()` | `string` | `'Partners'` |
| `jobTitle()` | `string` - one of 43 titles | `'Project Manager'` |
| `industry()` | `string` - one of 15 industries | `'Consulting'` |
| `catchPhrase()` | `string` - three buzzwords | `'Multi-layered regional installation'` |

## Examples

```php
$company = $generator->company();

$client = [
    'name' => $company->company(),
    'industry' => $company->industry(),
    'contact' => $generator->person()->name(),
    'position' => $company->jobTitle(),
];
```

## Notes

* `COMPANY_FORMATS`: `'{lastName} {suffix}'`, `'{lastName} & {otherLastName}'`, `'{lastName}-{otherLastName} {suffix}'`, `'{lastName} {word} {suffix}'` (`{word}` from `NAME_WORD`) - with ~150 last names it gives thousands of combinations, the two last names are always different
* suffixes are neutral: `Group`, `Holding`, `International`, `Partners`, `Associates`, `Enterprises`, `Co`. Legal forms are country specific (`Inc`, `LLC`, `Ltd`, `GmbH`, `sp. z o.o.`) - language packs put them in `SUFFIX`
* company names use [Person](person.md) last names - replacing `Person` with `withDefinition()` changes them too
