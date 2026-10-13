<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use Quidam\Core\Payment as CorePayment;

/**
 * Prices as medieval accounts wrote them - pounds, shillings and pence (20 s. to the pound, 12 d. to the shilling)
 * in medieval numerals: £3.33 is "iij li. vj s. viij d."
 */
class Payment extends CorePayment
{
    use MedievalNumeralsTrait;

    /**
     * e.g. formatPrice(3.34) -> "iij li. vj s. ix d.", formatPrice(0.5) -> "x s."; amounts round to the penny
     */
    public function formatPrice(float $amount): string
    {
        $pence = (int) round(abs($amount) * 240);
        $parts = array_filter([
            'li.' => intdiv($pence, 240),
            's.' => intdiv($pence % 240, 12),
            'd.' => $pence % 12,
        ]);

        if ($parts === []) {
            return 'nought';
        }

        return ($amount < 0 ? '-' : '') . implode(' ', array_map(
            fn (string $unit, int $value): string => $this->medievalNumeral($value) . ' ' . $unit,
            array_keys($parts),
            $parts,
        ));
    }
}
