<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface CommerceExtensionInterface extends ExtensionInterface
{
    /**
     * Adjective, material and product, e.g. "Ergonomic Wooden Chair"
     */
    public function productName(): string;

    /**
     * e.g. "Ergonomic"
     */
    public function productAdjective(): string;

    /**
     * e.g. "Wooden"
     */
    public function productMaterial(): string;

    /**
     * e.g. "Chair"
     */
    public function product(): string;

    /**
     * Shop department / category, e.g. "Garden"
     */
    public function department(): string;

    /**
     * Stock keeping unit, e.g. "KXP-48213"
     */
    public function sku(): string;

    /**
     * Discount code, e.g. "SUMMER20"
     */
    public function promotionCode(): string;
}
