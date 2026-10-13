<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use Quidam\Core\Payment as CorePayment;

/**
 * Prices as Babylonian contracts wrote them - silver by weight: 60 shekels (šiqil) to the mina (mana), 180 grains
 * (uṭṭet) to the shekel; a shekel (8.3 g of silver) counts as 10 of the currency: 1234.5 is "2 mana 3 šiqil 81 uṭṭet
 * kaspim"
 */
class Payment extends CorePayment
{
    /** Grains per unit of the currency - 180 to a shekel worth 10 */
    private const int GRAINS = 18;

    /**
     * e.g. formatPrice(1234.5) -> "2 mana 3 šiqil 81 uṭṭet kaspim" (of silver), formatPrice(10) -> "1 šiqil kaspim";
     * amounts round to the grain
     */
    public function formatPrice(float $amount): string
    {
        $grains = (int) round(abs($amount) * self::GRAINS);
        $parts = array_filter([
            'mana' => intdiv($grains, 10800),
            'šiqil' => intdiv($grains % 10800, 180),
            'uṭṭet' => $grains % 180,
        ]);

        if ($parts === []) {
            return 'ul mimma';
        }

        return ($amount < 0 ? '-' : '') . implode(' ', array_map(
            static fn (string $unit, int $value): string => $value . ' ' . $unit,
            array_keys($parts),
            $parts,
        )) . ' kaspim';
    }
}
