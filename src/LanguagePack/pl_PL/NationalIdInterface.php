<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use DateTimeInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\ExtensionInterface;

/**
 * Polish identifiers - the pack's own extension, registered under this interface like core extensions, so it can be
 * replaced with withDefinition()
 */
interface NationalIdInterface extends ExtensionInterface
{
    /**
     * PESEL - 11 digits encoding birth date and gender, e.g. "90010112345". Pass birth date and gender of a person
     * to match it (i.e. from profile()->person()), random when null. Birth years 1800-2299.
     */
    public function pesel(?DateTimeInterface $birthDate = null, ?Gender $gender = null): string;

    /**
     * NIP (tax identification number) - 10 digits, e.g. "5261040828" or formatted "526-104-08-28"
     */
    public function nip(bool $formatted = false): string;

    /**
     * REGON (business register number) - 9 digits, e.g. "012345675"
     */
    public function regon(): string;

    /**
     * REGON of a local unit - 14 digits (9-digit REGON, 4-digit unit, check digit)
     */
    public function regonLocal(): string;

    /**
     * ID card (dowód osobisty) number - 3 letters, check digit, 5 digits, e.g. "ABA300000"
     */
    public function idCardNumber(): string;

    /**
     * Passport number - series "E" + letter (biometric passports), check digit, 6 digits, e.g. "ES5182736"
     */
    public function passportNumber(): string;

    /**
     * Land registry (księga wieczysta) number - real court code, 8 digits, check digit, e.g. "WA1M/00012345/6"
     */
    public function landRegistryNumber(): string;

    /**
     * Doctor's licence number (numer prawa wykonywania zawodu, PWZ) - 7 digits, the first is a check digit
     */
    public function doctorLicenceNumber(): string;

    /**
     * National Court Register (KRS) number - 10 digits with leading zeros, e.g. "0000123456"
     */
    public function krs(): string;

    /**
     * EU VAT number - "PL" and NIP, e.g. "PL5261040828"
     */
    public function euVatNumber(): string;
}
