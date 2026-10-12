# Address

`$generator->address()` - interface `Quidam\Definitions\Extension\AddressExtensionInterface`, default implementation `Quidam\Core\Address`

Street addresses, regions, countries with ISO codes and geographic coordinates. Street and city names are built from [Person](person.md) names (English); formats are neutral samples without a country - see [Locale](../locale.md).

## Methods

| Method | Returns | Example |
|---|---|---|
| `city()` | `string` | `'West Raymond'`, `'Cookstad'`, `'Ameliaside'` |
| `cityPrefix()` | `string` - first part of some city names | `'North'`, `'Port'` |
| `citySuffix()` | `string` - ending of some city names | `'ville'`, `'borough'` |
| `streetName()` | `string` | `'Hunter Square'` |
| `streetSuffix()` | `string` | `'Boulevard'` |
| `secondaryAddress()` | `string` - apartment, suite, unit or floor | `'Apt. 24'`, `'Suite 210'` |
| `buildingNumber()` | `string` - 1-3 digits, small numbers more often | `'6'`, `'42'`, `'422'` |
| `streetAddress()` | `string` - `STREET_ADDRESS_FORMAT` | `'Hunter Square 42'` |
| `postcode()` | `string` - `#####` or `####` | `'45315'`, `'8822'` |
| `address()` | `string` - `ADDRESS_FORMAT` | `'Hunter Square 42, 53154 West Raymond'` |
| `state()` | `string` - state / province / region | `'Capital District'` |
| `stateCode()` | `string` | `'CD'` |
| `stateData()` | `array{code, name}` - matching values | `['code' => 'NR', 'name' => 'Northern Region']` |
| `county()` | `string` - `COUNTY_FORMAT`, fictional | `'Fletcher District'` |
| `cityData(?string $stateCode = null)` | `array{city, state, stateCode, county, postcode, latitude, longitude}` - one place, optionally of a region | `['city' => 'Cookstad', 'state' => 'Southern Region', 'stateCode' => 'SR', ...]` |
| `addressData(?string $stateCode = null)` | `cityData()` + `street` and the formatted `address` line built from the parts | `['street' => 'Hunter Square 42', ..., 'address' => 'Hunter Square 42, 9719 South Sylviabury']` |
| `country()` | `string` - English short name | `'Iceland'` |
| `countryCode()` | `string` - ISO 3166-1 alpha-2 | `'UM'` |
| `countryCodeAlpha3()` | `string` - ISO 3166-1 alpha-3 | `'VIR'` |
| `countryData()` | `array{alpha2, alpha3, name}` - matching values | `['alpha2' => 'TH', 'alpha3' => 'THA', 'name' => 'Thailand']` |
| `latitude(float $min = -90.0, float $max = 90.0)` | `float` - 6 decimals | `67.593119` |
| `longitude(float $min = -180.0, float $max = 180.0)` | `float` - 6 decimals | `-52.079243` |
| `coordinates()` | `array{latitude, longitude}` | `['latitude' => 5.110551, 'longitude' => 88.442004]` |

## Examples

```php
$address = $generator->address();

$address->address();                    // 'Hunter Square 42, 53154 West Raymond'

// one record where the parts belong together
$data = $address->addressData('CR');    // ['street' => ..., 'state' => 'Central Region', 'stateCode' => 'CR', ..., 'address' => ...]

// matching country data, i.e. for a "countries" table
$country = $address->countryData();     // ['alpha2' => 'TH', 'alpha3' => 'THA', 'name' => 'Thailand']

// coordinates in given area (here: Poland, roughly)
$latitude = $address->latitude(49.0, 54.8);
$longitude = $address->longitude(14.1, 24.1);
```

## Formats

The layout comes from overridable templates, so language packs change data, not code:

| Constant | Default | Placeholders |
|---|---|---|
| `STREET_ADDRESS_FORMAT` | `'{street} {buildingNumber}'` | `{street}` (`streetName()`), `{buildingNumber}` |
| `ADDRESS_FORMAT` | `'{street}, {postcode} {city}'` | `{street}` (street line), `{postcode}`, `{city}`; in `addressData()` also `{state}`, `{stateCode}`, `{county}` |
| `COUNTY_FORMAT` | `'{name} District'` | `{name}` (a last name) |

`address()` and `addressData()` both go through the protected `formatAddress()` - placeholders without a value are removed together with the separator before them. Data constants: `CITY_PREFIX`, `CITY_SUFFIX`, `STREET_SUFFIX`, `BUILDING_NUMBER`, `POSTCODE`, `SECONDARY_ADDRESS` (masks for `numerify()`, `%` is a non-zero digit), `STATES` (code => name), `COUNTRIES`.

```php
use Quidam\Core\Address;

// US layout - en_US pack
class UsAddress extends Address
{
    protected const string STREET_ADDRESS_FORMAT = '{buildingNumber} {street}';
    protected const string ADDRESS_FORMAT = '{street}, {city}, {stateCode} {postcode}';
    protected const string COUNTY_FORMAT = '{name} County';

    /** @var list<string> */
    protected const array BUILDING_NUMBER = ['%####', '%###', '%##', '%#'];

    /** @var list<string> */
    protected const array POSTCODE = ['#####'];

    /** @var array<string, string> */
    protected const array STATES = ['CA' => 'California', 'TX' => 'Texas', 'NY' => 'New York' /* ... */];
}

$us = $generator->withDefinition(AddressExtensionInterface::class, UsAddress::class);
$us->address()->addressData()['address'];   // '4821 Fisher Street, Lakeville, TX 12345'
```

## Notes

* default data is a **neutral sample without a country**: English city and street names, "street number, postcode city" order (the most common one worldwide), plain digit postcodes, generic regions (`Northern Region`, `Capital District`...) - see [Locale](../locale.md)
* `cityData()` / `addressData()` are records whose parts belong together - unlike separate `city()` and `postcode()` calls. Default data is fictional: region and code match, the address line is built from the parts, but coordinates are random. Language packs can return real places where city, region, county, postcode and coordinates match
* `cityData('XX')` with a code not in `STATES` throws `InvalidArgumentException`
* countries: all 249 ISO 3166-1 entries (from `iso-codes` data). `country()`, `countryCode()` and `countryCodeAlpha3()` use the same table, `countryData()` returns values of one country
* country names are English short names, some contain non-ASCII characters (`Türkiye`, `Côte d'Ivoire`, `Åland Islands`)
* `latitude()` spreads points evenly over Earth surface (not over degrees, which would crowd them at the poles), `coordinates()` uses it too. Results are rounded to 6 decimals (~10 cm) and always stay within given range
* range limits are validated: latitude -90..90, longitude -180..180, `min <= max`
