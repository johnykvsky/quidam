<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

use Quidam\Addon\Filler;

/**
 * Filler with own rule
 */
class ShopFiller extends Filler
{
    /** @var array<string, string> */
    protected const array NAME_RULES = [...parent::NAME_RULES, 'productcode' => 'commerce.sku'];
}
