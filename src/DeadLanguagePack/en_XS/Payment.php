<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use Quidam\Core\Payment as CorePayment;

/**
 * Prices in pounds, shillings and pence (20s. to the pound, 12d. to the shilling): £3.33 is "£3 6s. 8d."
 */
class Payment extends CorePayment
{
    /**
     * e.g. formatPrice(3.3334) -> "£3 6s. 8d.", formatPrice(0.5) -> "10s."; amounts round to the penny
     */
    public function formatPrice(float $amount): string
    {
        $pence = (int) round(abs($amount) * 240);
        $parts = array_filter([
            '£%d' => intdiv($pence, 240),
            '%ds.' => intdiv($pence % 240, 12),
            '%dd.' => $pence % 12,
        ]);

        if ($parts === []) {
            return 'nought';
        }

        return ($amount < 0 ? '-' : '') . implode(' ', array_map(sprintf(...), array_keys($parts), $parts));
    }
}
