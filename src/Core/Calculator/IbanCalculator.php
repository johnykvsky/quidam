<?php

declare(strict_types = 1);

namespace Quidam\Core\Calculator;

use Quidam\Definitions\Calculator\IbanCalculatorInterface;

class IbanCalculator implements IbanCalculatorInterface
{
    public function checksum(string $countryCode, string $bban): string
    {
        return sprintf('%02d', 98 - $this->mod97($bban . $countryCode . '00'));
    }

    public function isValid(string $iban): bool
    {
        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/', $iban) !== 1) {
            return false;
        }

        return $this->mod97(substr($iban, 4) . substr($iban, 0, 4)) === 1;
    }

    /**
     * Remainder of mod 97 with letters as numbers (A = 10 ... Z = 35)
     */
    protected function mod97(string $value): int
    {
        $numeric = preg_replace_callback('/[A-Z]/', static fn (array $letter): string => (string) (ord($letter[0]) - 55), $value);

        $remainder = 0;
        foreach (str_split((string) $numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}
