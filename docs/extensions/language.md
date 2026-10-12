# Language

`$generator->language()` - interface `Quidam\Definitions\Extension\LanguageExtensionInterface`, default implementation `Quidam\Core\Language`

Languages with ISO 639-1 codes and English names, locales.

## Methods

| Method | Returns | Example |
|---|---|---|
| `language()` | `string` - English name | `'Javanese'` |
| `languageCode()` | `string` - ISO 639-1 | `'ka'` |
| `languageData()` | `array{code, name}` - matching values | `['code' => 'ho', 'name' => 'Hiri Motu']` |
| `locale(string $separator = '_')` | `string` - language and country | `'en_AU'`, `locale('-')` → `'lv-LV'` |

## Examples

```php
$language = $generator->language();

$user = [
    'name' => $generator->person()->name(),
    'locale' => $language->locale(),          // 'en_AU'
];

$header = 'Accept-Language: ' . $language->locale('-');   // BCP 47 style 'pl-PL'

['code' => $code, 'name' => $name] = $language->languageData();
```

## Data

* 174 living languages - ISO 639-1 codes from `iso-codes` data, without historical (Latin, Sanskrit, Avestan...) and constructed (Esperanto, Volapük...) ones
* 236 locales - `language_COUNTRY` combinations supported by glibc, every locale language is in the languages list
* `locale()` separator: `_` (POSIX style, default) or `-` (BCP 47 style), anything else throws
