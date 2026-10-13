<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use Quidam\Core\Payment as CorePayment;

/**
 * Prices as medieval accounts wrote them - livres, sous and deniers tournois (20 sous to the livre, 12 deniers to the
 * sol) in Roman numerals: 3.33 is "III livres VI sous VIII deniers tornois"
 */
class Payment extends CorePayment
{
    /**
     * e.g. formatPrice(3.34) -> "III livres VI sous IX deniers tornois", formatPrice(0.05) -> "I sol tornois";
     * amounts round to the denier
     */
    public function formatPrice(float $amount): string
    {
        $deniers = (int) round(abs($amount) * 240);
        $parts = array_filter([
            'livre' => intdiv($deniers, 240),
            'sol' => intdiv($deniers % 240, 12),
            'denier' => $deniers % 12,
        ]);

        if ($parts === []) {
            return 'neant';
        }

        return ($amount < 0 ? '-' : '') . implode(' ', array_map(
            fn (string $unit, int $value): string => $this->roman($value) . ' ' . ($value === 1 ? $unit : self::plural($unit)),
            array_keys($parts),
            $parts,
        )) . ' tornois';
    }

    /** "sol" makes "sous", the others take "s" */
    private static function plural(string $unit): string
    {
        return $unit === 'sol' ? 'sous' : $unit . 's';
    }

    /** Roman numeral of 1-3999, larger numbers as digits */
    private function roman(int $number): string
    {
        if ($number > 3999) {
            return (string) $number;
        }

        $roman = '';
        foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }
}
