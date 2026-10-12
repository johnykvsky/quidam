<?php

declare(strict_types = 1);

namespace Quidam\Core\Calculator;

use Quidam\Definitions\Calculator\IsbnCalculatorInterface;
use Quidam\Exception\InvalidArgumentException;

class IsbnCalculator implements IsbnCalculatorInterface
{
    public function checkDigit(string $number): string
    {
        if (strlen($number) !== 9 || !ctype_digit($number)) {
            throw new InvalidArgumentException(sprintf('ISBN-10 check digit needs 9 digits, "%s" given', $number));
        }

        $sum = 0;
        foreach (str_split($number) as $position => $digit) {
            $sum += (10 - $position) * (int) $digit;
        }

        $checkDigit = (11 - $sum % 11) % 11;

        return $checkDigit === 10 ? 'X' : (string) $checkDigit;
    }

    public function isValid(string $number): bool
    {
        if (preg_match('/^\d{9}[\dX]$/', $number) !== 1) {
            return false;
        }

        return $this->checkDigit(substr($number, 0, 9)) === $number[9];
    }
}
