<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\CommerceExtensionInterface;

final class CommerceTest extends GeneratorTestCase
{
    public function testProducts(): void
    {
        $commerce = $this->dummy->ext(CommerceExtensionInterface::class);

        $this->assertAlwaysMatches('/^[A-Z][a-z]+( Duty)? [A-Z][a-z]+ [A-Z][a-z]+$/', $commerce->productName(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+( Duty)?$/', $commerce->productAdjective(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $commerce->productMaterial(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $commerce->product(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $commerce->department(...));
    }

    public function testCodes(): void
    {
        $commerce = $this->dummy->ext(CommerceExtensionInterface::class);

        $this->assertAlwaysMatches('/^[A-Z0-9-]{6,10}$/', $commerce->sku(...));
        $this->assertAlwaysMatches('/^[A-Z]+(5|10|15|20|25|30|40|50)$/', $commerce->promotionCode(...));
    }
}
