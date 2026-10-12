<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\CommerceExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

/**
 * Products, departments, SKUs and promotion codes - for e-commerce fixtures. Prices are in payment()->price().
 */
class Commerce implements CommerceExtensionInterface
{
    /** @var list<string> */
    protected const array PRODUCT_ADJECTIVE = [
        'Awesome', 'Classic', 'Compact', 'Durable', 'Elegant', 'Ergonomic', 'Essential', 'Fantastic', 'Handcrafted',
        'Handmade', 'Heavy Duty', 'Incredible', 'Intelligent', 'Lightweight', 'Luxurious', 'Modern', 'Practical',
        'Premium', 'Recycled', 'Refined', 'Rustic', 'Sleek', 'Small', 'Smart', 'Sturdy', 'Tasty', 'Vintage',
    ];

    /** @var list<string> */
    protected const array PRODUCT_MATERIAL = [
        'Aluminum', 'Bamboo', 'Bronze', 'Ceramic', 'Concrete', 'Copper', 'Cotton', 'Glass', 'Granite', 'Leather',
        'Linen', 'Marble', 'Metal', 'Oak', 'Plastic', 'Rubber', 'Silk', 'Steel', 'Wooden', 'Wool',
    ];

    /** @var list<string> */
    protected const array PRODUCT = [
        'Backpack', 'Bag', 'Ball', 'Bench', 'Bike', 'Bottle', 'Bowl', 'Car', 'Chair', 'Clock', 'Computer', 'Gloves',
        'Hat', 'Headphones', 'Keyboard', 'Knife', 'Lamp', 'Mouse', 'Mug', 'Pants', 'Pillow', 'Plate', 'Shirt', 'Shoes',
        'Sofa', 'Table', 'Towel', 'Wallet', 'Watch',
    ];

    /** @var list<string> */
    protected const array DEPARTMENT = [
        'Automotive', 'Baby', 'Beauty', 'Books', 'Clothing', 'Computers', 'Electronics', 'Games', 'Garden', 'Grocery',
        'Health', 'Home', 'Industrial', 'Jewelry', 'Kids', 'Movies', 'Music', 'Outdoors', 'Shoes', 'Sports', 'Tools',
        'Toys',
    ];

    /** @var list<string> masks for strings()->bothify(), result is uppercase */
    protected const array SKU_FORMATS = ['???-#####', '??-####-??', '???###', '##-???-###'];

    /** @var list<string> */
    protected const array PROMOTION_WORD = [
        'SALE', 'SAVE', 'WELCOME', 'SUMMER', 'WINTER', 'SPRING', 'BLACKFRIDAY', 'VIP', 'FREESHIP', 'DEAL', 'NEWYEAR',
        'THANKS', 'EXTRA', 'FLASH',
    ];

    /** @var list<int> */
    protected const array PROMOTION_DISCOUNT = [5, 10, 15, 20, 25, 30, 40, 50];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly StringsExtensionInterface $strings,
    ) {
    }

    /**
     * Adjective, material and product, e.g. "Ergonomic Wooden Chair"
     */
    public function productName(): string
    {
        return $this->productAdjective() . ' ' . $this->productMaterial() . ' ' . $this->product();
    }

    /**
     * e.g. "Ergonomic"
     */
    public function productAdjective(): string
    {
        return $this->randomizer->randomElement(static::PRODUCT_ADJECTIVE);
    }

    /**
     * e.g. "Wooden"
     */
    public function productMaterial(): string
    {
        return $this->randomizer->randomElement(static::PRODUCT_MATERIAL);
    }

    /**
     * e.g. "Chair"
     */
    public function product(): string
    {
        return $this->randomizer->randomElement(static::PRODUCT);
    }

    /**
     * Shop department / category, e.g. "Garden"
     */
    public function department(): string
    {
        return $this->randomizer->randomElement(static::DEPARTMENT);
    }

    /**
     * Stock keeping unit, e.g. "KXP-48213"
     */
    public function sku(): string
    {
        return strtoupper($this->strings->bothify($this->randomizer->randomElement(static::SKU_FORMATS)));
    }

    /**
     * Discount code, e.g. "SUMMER20"
     */
    public function promotionCode(): string
    {
        return $this->randomizer->randomElement(static::PROMOTION_WORD) . $this->randomizer->randomElement(static::PROMOTION_DISCOUNT);
    }
}
