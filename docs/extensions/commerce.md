# Commerce

`$generator->commerce()` - interface `Quidam\Definitions\Extension\CommerceExtensionInterface`, default implementation `Quidam\Core\Commerce`

Products, departments, SKUs and promotion codes - for e-commerce fixtures. Prices are in [`Payment::price()`](payment.md) (or `priceInMinorUnits()` for integer cents).

## Methods

| Method | Returns | Example |
|---|---|---|
| `productName()` | `string` - adjective, material and product | `'Awesome Granite Backpack'` |
| `productAdjective()` | `string` | `'Luxurious'` |
| `productMaterial()` | `string` | `'Bronze'` |
| `product()` | `string` | `'Sofa'` |
| `department()` | `string` - shop department / category | `'Tools'` |
| `sku()` | `string` - uppercase stock keeping unit | `'31-OBP-540'`, `'KXP-48213'` |
| `promotionCode()` | `string` - word and discount | `'SPRING20'` |

## Examples

```php
$commerce = $generator->commerce();

$product = [
    'sku' => $generator->unique('products.sku', fn (Quidam $d) => $d->commerce()->sku()),
    'name' => $commerce->productName(),
    'category' => $commerce->department(),
    'price' => $generator->payment()->price(5, 500),
    'stock' => $generator->number()->gaussianInt(40, 25, 0),
];

$coupon = ['code' => $commerce->promotionCode(), 'validUntil' => $generator->dateTime()->dateTimeFuture('+3 months')];
```

## Notes

* overridable data: `PRODUCT_ADJECTIVE`, `PRODUCT_MATERIAL`, `PRODUCT`, `DEPARTMENT`, `SKU_FORMATS` (masks for [`Strings::bothify()`](strings.md), uppercased), `PROMOTION_WORD`, `PROMOTION_DISCOUNT`
* adjective, material and product are picked independently - `'Tasty Steel Shoes'` can happen, like in Faker
* SKUs aren't unique by themselves - wrap in `unique()` for a unique column
