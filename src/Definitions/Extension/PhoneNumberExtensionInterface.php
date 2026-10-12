<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface PhoneNumberExtensionInterface extends ExtensionInterface
{
    /**
     * National (landline) number as written locally, e.g. "020 7946 0000" - sample format without country by default
     */
    public function phoneNumber(): string;

    /**
     * National mobile number as written locally, e.g. "0712 345678"
     */
    public function mobilePhoneNumber(): string;

    /**
     * Number with country calling code, as written internationally, e.g. "+44 20 7946 0000" - built from a national
     * (landline or mobile) number of the locale; a random real country and digits when locale has no calling code
     */
    public function internationalPhoneNumber(): string;

    /**
     * Phone number in E.164 format, e.g. "+48123456789" - of the locale country, a random real country when locale
     * has no calling code. With $countryCode (ISO 3166-1 alpha-2, e.g. "PL") of that country - national formats of
     * the locale when it's the locale country, otherwise calling code and number of digits of that country.
     */
    public function e164PhoneNumber(?string $countryCode = null): string;

    /**
     * 15-digit IMEI (device identifier) with valid Luhn check digit, e.g. "356938035643809"
     */
    public function imei(): string;
}
