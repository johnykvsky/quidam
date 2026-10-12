<?php

declare(strict_types = 1);

namespace Quidam\Core\Calculator;

use Quidam\Definitions\Calculator\LuhnCalculatorInterface;

class LuhnCalculator implements LuhnCalculatorInterface
{
    public function checkDigit(string $number): string
    {
        $sum = 0;

        // check digit will be appended, so doubling starts from the rightmost digit
        foreach (array_reverse(str_split($number)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 0 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
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
