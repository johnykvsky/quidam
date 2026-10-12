# PhoneNumber

`$generator->phoneNumber()` - interface `Quidam\Definitions\Extension\PhoneNumberExtensionInterface`, default implementation `Quidam\Core\PhoneNumber`

National numbers (landline and mobile), international and E.164 formats, IMEI. National formats come from the extension, the country calling code from the [locale](../locale.md) - so one language pack keeps them consistent.

## Methods

| Method | Returns | Example |
|---|---|---|
| `phoneNumber()` | `string` - national landline number | `'034 062 0174'`, `'05 40 89 39 71'` |
| `mobilePhoneNumber()` | `string` - national mobile number | `'07888 228453'`, `'0694 089 397'` |
| `internationalPhoneNumber()` | `string` - `+` calling code and national number without trunk prefix | `'+385 888 228 453'`, Polish pack `'+48 512 345 678'` |
| `e164PhoneNumber(?string $countryCode = null)` | `string` - `+` calling code and digits, max 15 digits; of given country (ISO 3166-1 alpha-2) when set | `'+385888228453'`, `e164PhoneNumber('DE')` → `'+4915123456789'` |
| `imei()` | `string` - 15-digit device identifier, valid Luhn check digit | `'868822845315405'` |

## Examples

```php
$phone = $generator->phoneNumber();

$contact = [
    'name' => $generator->person()->name(),
    'phone' => $phone->phoneNumber(),
    'mobile' => $phone->e164PhoneNumber(),     // i.e. for SMS gateways expecting E.164
];

$phone->e164PhoneNumber('DE');   // '+4915123456789' - German calling code, whatever the locale
```

## Without and with a country

| | Locale without calling code (default) | Locale with calling code (language pack) |
|---|---|---|
| `phoneNumber()`, `mobilePhoneNumber()` | `FORMATS`, `MOBILE_FORMATS` - neutral samples: trunk prefix `0`, area code, subscriber number | the pack's `FORMATS`, `MOBILE_FORMATS` |
| `internationalPhoneNumber()` | random real country (`E164_FORMATS`), digits in groups of 3 | `+` locale calling code + a national number (landline or mobile) without the locale trunk prefix |
| `e164PhoneNumber()` | random real country (`E164_FORMATS`) | the same number as above, digits only |
| `e164PhoneNumber('DE')` | calling code of the country (`COUNTRY_CALLING_CODES`) and its number of digits from `E164_FORMATS` | the same; locale formats when it's the locale country (`'PL'` in a Polish pack) |

The trunk prefix is the leading digit dialled only inside the country - `0` in most of Europe, none in Poland or the US. It comes from `LocaleInterface::trunkPrefix()` (`'0'` by default) and is removed when the number is written with a calling code - `'020 7946 0000'` becomes `'+44 20 7946 0000'`.

```php
use Quidam\Core\Locale\Locale;
use Quidam\Core\PhoneNumber;
use Quidam\Definitions\Locale\LocaleInterface;

final class PlPhoneNumber extends PhoneNumber
{
    /** @var list<string> */
    protected const array FORMATS = ['%# ### ## ##'];                                         // 22 123 45 67

    /** @var list<string> */
    protected const array MOBILE_FORMATS = ['5## ### ###', '6## ### ###', '7## ### ###', '8## ### ###'];
}

$pl = $generator
    ->withDefinition(LocaleInterface::class, new Locale(countryCode: 'PL', callingCode: '48', trunkPrefix: ''))
    ->withDefinition(PhoneNumberExtensionInterface::class, PlPhoneNumber::class);

$pl->phoneNumber()->internationalPhoneNumber();   // '+48 512 345 678'
$pl->phoneNumber()->e164PhoneNumber();            // '+48221234567'
```

## Notes

* formats are masks for `strings()->numerify()`: `#` any digit, `%` a non-zero digit
* default national formats are **samples without a country**, not a real numbering plan - see [Locale](../locale.md)
* `E164_FORMATS` has 206 country calling codes with the length of an example mobile number (national significant number) from [libphonenumber](https://github.com/google/libphonenumber) - `+48` and 9 digits, `+86` and 11. Masks are `'+48 %########'` - calling code, space, national number; the national number starts with a non-zero digit, except where it starts with `0` in reality (Benin and Côte d'Ivoire `01`, Gabon and Congo `06` - kept literally). Digits after the first are random, so a strict validator (libphonenumber `isValidNumber()`) can reject them - realistic ranges come from language packs. Non-geographic codes (`+800` freephone, satellite services) aren't in it
* `COUNTRY_CALLING_CODES` maps 242 ISO 3166-1 alpha-2 countries to calling codes - countries sharing one (`US` and `CA` share `+1`, `RU` and `KZ` share `+7`) get the same format. Countries without own numbers (`AQ`, `BV`, `GS`, `HM`, `PN`, `TF`, `UM`), unknown or lowercase codes throw `InvalidArgumentException`
* both tables are generated from libphonenumber's metadata by `tools/e164-formats.php` - to update them, download `PhoneNumberMetadata.xml` of a release and run `php tools/e164-formats.php PhoneNumberMetadata.xml <tag>`
* numbers are not checked against real numbering plans - they can belong to someone, don't call or text them
* toll free numbers are country specific and not in the core - they belong to language packs (US / Canada `800`, `888`...; UK `0800`); the included [pl_PL pack](../../src/LanguagePack/pl_PL/README.md) has `tollFreePhoneNumber()` and real Polish area codes and mobile prefixes
* `imei()` starts with a real reporting body identifier (`35`, `86`, `01`, `99`), the rest is random - it passes IMEI validation but doesn't identify a real device model. Check digit comes from `LuhnCalculatorInterface` - see [Calculators](../extending.md#calculators)
