<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Calculator;

/**
 * Luhn (mod 10) - card numbers, IMEI
 */
interface LuhnCalculatorInterface extends CalculatorInterface
{
    /**
     * Check digit to append to given digits
     */
    public function checkDigit(string $number): string;

    /**
     * True when last digit is valid Luhn check digit of the digits before it
     */
    public function isValid(string $number): bool;
}
