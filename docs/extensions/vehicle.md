# Vehicle

`$generator->vehicle()` - interface `Quidam\Definitions\Extension\VehicleExtensionInterface`, default implementation `Quidam\Core\Vehicle`

Manufacturers with matching models, body types, fuel, VIN with valid check digit and licence plates.

## Methods

| Method | Returns | Example |
|---|---|---|
| `vehicle()` | `string` - manufacturer and its model | `'Skoda Kodiaq'` |
| `manufacturer()` | `string` | `'Nissan'` |
| `model(?string $manufacturer = null)` | `string` - model of given (or random) manufacturer | `model('BMW')` → `'i4'` |
| `type()` | `string` - body type | `'Sedan'`, `'SUV'` |
| `fuel()` | `string` - picked by popularity | `'Gasoline'`, `'Electric'` |
| `vin(?string $manufacturer = null)` | `string` - 17 characters, valid check digit | `'VF3MK2XC0M7872463'`, `vin('Toyota')` → `'JTETD2K59D4196374'` |
| `licensePlate()` | `string` - uppercase, generic sample format | `'IM 8822X'`, `'T97 KQQ'` |

## Examples

```php
$vehicle = $generator->vehicle();
$manufacturer = $vehicle->manufacturer();

$car = [
    'manufacturer' => $manufacturer,
    'model' => $vehicle->model($manufacturer),          // matches manufacturer
    'vin' => $vehicle->vin($manufacturer),              // real WMI prefix of that manufacturer
    'plate' => $vehicle->licensePlate(),
    'fuel' => $vehicle->fuel(),
];
```

## VIN

* structure (ISO 3779): real World Manufacturer Identifier (`WBA` BMW, `5YJ` Tesla...), 5 descriptor characters, check digit, model year code, plant code, 6-digit serial
* check digit (9th character) follows the North American rule (weighted sum mod 11, `X` for 10) - it passes VIN validators; outside North America the check digit is optional, so it's valid there too
* never contains `I`, `O`, `Q`
* the check digit calculation is the protected `vinCheckDigit()` - override it in a subclass for invalid VINs in negative tests (see [Calculators](../extending.md#calculators))

## Notes

* overridable data: `MANUFACTURERS` (manufacturer => `wmi` list and `models` list), `TYPES`, `FUELS` (fuel => weight), `LICENSE_PLATE_FORMATS` (masks for [`Strings::bothify()`](strings.md), uppercased)
* default plate formats are generic samples (`'?? #####'`, `'?? ####?'`, `'??-###-??'`, `'?## ???'`) - language packs override `LICENSE_PLATE_FORMATS` with real ones (i.e. `['?? #####', '??? ####']` for Poland, `['???-####']` US-like)
* unknown manufacturer in `model()` / `vin()` throws `InvalidArgumentException` listing known ones
