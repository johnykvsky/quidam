<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Locale;

use Quidam\Definitions\DefinitionInterface;

/**
 * Conventions of the country data belongs to - shared by extensions, so one definition keeps country, phone calling
 * code, currency and formats consistent. Language data (names, words, month names) stays in extensions.
 *
 * Default is international sample data: no country, EUR, ISO dates. Language packs replace it:
 *
 *   LocaleInterface::class => new Locale(countryCode: 'PL', languageCode: 'pl', callingCode: '48', currencyCode: 'PLN', ...)
 */
interface LocaleInterface extends DefinitionInterface
{
    /**
     * ISO 3166-1 alpha-2 country, e.g. "PL" - null for international data (no country)
     */
    public function countryCode(): ?string;

    /**
     * ISO 639-1 language of generated words, e.g. "en"
     */
    public function languageCode(): string;

    /**
     * Country calling code without "+", e.g. "48" - null for international data (random country in E.164 numbers)
     */
    public function callingCode(): ?string;

    /**
     * Trunk prefix dropped from national numbers in international format, e.g. "0" ("020 7946 0000" is
     * "+44 20 7946 0000"), "" when there is none
     */
    public function trunkPrefix(): string;

    /**
     * ISO 4217 currency, e.g. "EUR"
     */
    public function currencyCode(): string;

    /**
     * Currency symbol as written locally, e.g. "€", "zł" - code when there's no symbol
     */
    public function currencySymbol(): string;

    public function decimalSeparator(): string;

    public function thousandsSeparator(): string;

    /**
     * Price layout - "{amount}", "{symbol}" and "{code}" are replaced, e.g. "{symbol}{amount}" or "{amount} {symbol}"
     */
    public function pricePattern(): string;

    /**
     * Numeric date - DateTimeInterface::format() pattern, e.g. "Y-m-d", "d.m.Y"
     */
    public function dateFormat(): string;

    /**
     * Time - DateTimeInterface::format() pattern, e.g. "H:i", "g:i A"
     */
    public function timeFormat(): string;

    /**
     * Date with month name - "{day}", "{month}" and "{year}" are replaced, e.g. "{day} {month} {year}"
     */
    public function longDateFormat(): string;
}
