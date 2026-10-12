<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Calculator;

/**
 * GS1 mod 10 - EAN-8, EAN-13, UPC-A, ISBN-13, ISMN
 */
interface EanCalculatorInterface extends CalculatorInterface
{
    /**
     * Check digit to append to given digits (weights 3, 1, 3... from the rightmost digit)
     */
    public function checkDigit(string $number): string;

    /**
     * True when last digit is valid GS1 check digit of the digits before it
     */
    public function isValid(string $number): bool;
}
