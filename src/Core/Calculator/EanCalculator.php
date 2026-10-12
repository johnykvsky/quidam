<?php

declare(strict_types = 1);

namespace Quidam\Core\Calculator;

use Quidam\Definitions\Calculator\EanCalculatorInterface;

class EanCalculator implements EanCalculatorInterface
{
    public function checkDigit(string $number): string
    {
        $sum = 0;

        // weights 3, 1, 3... from the rightmost digit (check digit is appended after it)
        foreach (array_reverse(str_split($number)) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 === 0 ? 3 : 1);
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    public function isValid(string $number): bool
    {
        if (strlen($number) < 2 || !ctype_digit($number)) {
            return false;
        }

        return $this->checkDigit(substr($number, 0, -1)) === substr($number, -1);
    }
}
