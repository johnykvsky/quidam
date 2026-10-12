<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Calculator;

/**
 * ISBN-10 mod 11
 */
interface IsbnCalculatorInterface extends CalculatorInterface
{
    /**
     * Check digit for 9 digits - "0"-"9" or "X" (10)
     */
    public function checkDigit(string $number): string;

    /**
     * True when given 10 characters are valid ISBN-10
     */
    public function isValid(string $number): bool;
}
