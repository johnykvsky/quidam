<?php

declare(strict_types = 1);

namespace Quidam\Core\Locale;

use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Locale as values - defaults are international sample data: no country, English words, EUR, ISO 8601 dates, 24-hour
 * time, "1,234.56 EUR". Language packs pass their values, i.e.
 *
 *   new Locale(countryCode: 'PL', languageCode: 'pl', callingCode: '48', trunkPrefix: '', currencyCode: 'PLN',
 *       currencySymbol: 'zł', decimalSeparator: ',', thousandsSeparator: "\u{00A0}", pricePattern: "{amount}\u{00A0}{symbol}",
 *       dateFormat: 'd.m.Y', longDateFormat: '{day} {month} {year}')
 */
readonly class Locale implements LocaleInterface
{
    public function __construct(
        private ?string $countryCode = null,
        private string $languageCode = 'en',
        private ?string $callingCode = null,
        private string $trunkPrefix = '0',
        private string $currencyCode = 'EUR',
        private string $currencySymbol = '€',
        private string $decimalSeparator = '.',
        private string $thousandsSeparator = ',',
        private string $pricePattern = "{amount}\u{00A0}{code}",
        private string $dateFormat = 'Y-m-d',
        private string $timeFormat = 'H:i',
        private string $longDateFormat = '{day} {month} {year}',
    ) {
        if ($countryCode !== null && preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            throw new InvalidArgumentException(sprintf('Country code must be ISO 3166-1 alpha-2 (uppercase), "%s" given', $countryCode));
        }

        if ($callingCode !== null && preg_match('/^[1-9]\d{0,2}$/', $callingCode) !== 1) {
            throw new InvalidArgumentException(sprintf('Calling code must be 1-3 digits without "+", "%s" given', $callingCode));
        }

        if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
            throw new InvalidArgumentException(sprintf('Currency code must be ISO 4217, "%s" given', $currencyCode));
        }
    }

    public function countryCode(): ?string
    {
        return $this->countryCode;
    }

    public function languageCode(): string
    {
        return $this->languageCode;
    }

    public function callingCode(): ?string
    {
        return $this->callingCode;
    }

    public function trunkPrefix(): string
    {
        return $this->trunkPrefix;
    }

    public function currencyCode(): string
    {
        return $this->currencyCode;
    }

    public function currencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function decimalSeparator(): string
    {
        return $this->decimalSeparator;
    }

    public function thousandsSeparator(): string
    {
        return $this->thousandsSeparator;
    }

    public function pricePattern(): string
    {
        return $this->pricePattern;
    }

    public function dateFormat(): string
    {
        return $this->dateFormat;
    }

    public function timeFormat(): string
    {
        return $this->timeFormat;
    }

    public function longDateFormat(): string
    {
        return $this->longDateFormat;
    }
}
