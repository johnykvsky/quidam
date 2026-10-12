<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Calculator;

/**
 * ISO 7064 mod 97-10 - IBAN check digits
 */
interface IbanCalculatorInterface extends CalculatorInterface
{
    /**
     * Two check digits placed after country code, e.g. checksum('PL', '10901014000071219812874') === '61'
     */
    public function checksum(string $countryCode, string $bban): string;

    /**
     * True when IBAN (without spaces) has valid check digits
     */
    public function isValid(string $iban): bool;
}
