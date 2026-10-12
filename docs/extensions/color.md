# Color

`$generator->color()` - interface `Quidam\Definitions\Extension\ColorExtensionInterface`, default implementation `Quidam\Core\Color`

Colors in hex, RGB and HSL formats, CSS color names.

## Methods

| Method | Returns | Example |
|---|---|---|
| `hexColor()` | `string` - `#rrggbb`, full range `#000000` - `#ffffff` | `'#2c8023'` |
| `safeHexColor()` | `string` - color expressible as `#rgb`, written in full | `'#cc6666'` |
| `rgbColorAsArray()` | `array{int, int, int}` - red, green, blue (0-255) | `[137, 21, 69]` |
| `rgbColor()` | `string` | `'212,16,178'` |
| `rgbCssColor()` | `string` | `'rgb(101,234,168)'` |
| `rgbaCssColor()` | `string` - alpha with up to 2 decimals | `'rgba(234,134,242,0.3)'` |
| `hslColorAsArray()` | `array{int, int, int}` - hue (0-359), saturation and lightness (0-100) | `[130, 56, 97]` |
| `hslColor()` | `string` | `'166,88,78'` |
| `hslCssColor()` | `string` | `'hsl(132, 57%, 93%)'` |
| `safeColorName()` | `string` - one of 16 basic colors, lowercase | `'aqua'` |
| `colorName()` | `string` - one of 148 CSS named colors | `'DarkKhaki'` |

## Examples

```php
$color = $generator->color();

$tag = [
    'name' => $generator->lorem()->word(),
    'color' => $color->hexColor(),          // '#2c8023'
];

$style = sprintf('background: %s; color: %s;', $color->hslCssColor(), $color->safeColorName());
```

## Notes

* `safeColorName()`: `aqua`, `black`, `blue`, `fuchsia`, `gray`, `green`, `lime`, `maroon`, `navy`, `olive`, `purple`, `red`, `silver`, `teal`, `white`, `yellow`
* `colorName()`: all CSS Color Module Level 4 named colors in CamelCase (`RebeccaPurple`, `DarkOrange`, both `Gray` and `Grey` spellings)
