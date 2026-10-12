<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface PaymentExtensionInterface extends ExtensionInterface
{
    /**
     * Card brand, picked by popularity, e.g. "Visa"
     */
    public function creditCardType(): string;

    /**
     * Card number with valid prefix, length and Luhn check digit, e.g. "4539148803436467" or formatted "4539-1488-0343-6467"
     *
     * @param string|null $type Card brand (see creditCardType()), random when null
     */
    public function creditCardNumber(?string $type = null, bool $formatted = false, string $separator = '-'): string;

    /**
     * Card number with all but last 4 digits masked, grouped like on the card, e.g. "**** **** **** 6467"
     *
     * @param string|null $type Card brand (see creditCardType()), random when null
     */
    public function maskedCreditCardNumber(?string $type = null, string $maskCharacter = '*'): string;

    /**
     * Expiration month, e.g. "09/28". Valid one is in current month or up to 5 years ahead (clock "now"),
     * expired one is in last 3 years.
     */
    public function creditCardExpirationDate(bool $valid = true, string $format = 'm/y'): string;

    /**
     * Security code - 4 digits for American Express, 3 for others
     *
     * @param string|null $type Card brand (see creditCardType())
     */
    public function creditCardCvv(?string $type = null): string;

    /** @return array{type: string, number: string, name: string, expirationDate: string, cvv: string} */
    public function creditCardDetails(bool $valid = true): array;

    /**
     * IBAN with valid length, structure and checksum, e.g. "PL61109010140000071219812874"
     * or formatted "PL61 1090 1014 0000 0712 1981 2874"
     *
     * @param string|null $countryCode ISO 3166-1 alpha-2 code of country using IBAN - when null the locale country
     *                                 (if it uses IBAN), otherwise random
     * @param string $prefix Beginning of the account number (BBAN), i.e. bank code
     */
    public function iban(?string $countryCode = null, string $prefix = '', bool $formatted = false): string;

    /**
     * SWIFT / BIC code: 8 characters, or 11 with branch code, e.g. "DEUTDEFF" or "DEUTDEFF500"
     */
    public function swiftBicNumber(): string;

    /**
     * ISO 4217 code, e.g. "EUR"
     */
    public function currencyCode(): string;

    /**
     * e.g. "Pound Sterling"
     */
    public function currencyName(): string;

    /**
     * Symbol of one of commonly used currencies, e.g. "€"
     */
    public function currencySymbol(): string;

    /**
     * Matching code, name and symbol of one currency, e.g. ['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł'].
     * Currencies without common symbol use code as symbol.
     *
     * @return array{code: string, name: string, symbol: string}
     */
    public function currencyData(): array;

    /**
     * Price within [$min, $max] with $decimals decimals
     */
    public function price(float $min = 0.0, float $max = 1000.0, int $decimals = 2): float;

    /**
     * Price in minor units of the currency (cents), as integer - like most money libraries store it. Decimals follow
     * ISO 4217: price 12.34 is 1234 in USD, 12 in JPY (no minor unit), 12340 in KWD (3 decimals)
     *
     * @param string|null $currencyCode ISO 4217 code, see currencyDecimals() - the local currency (localCurrency()) when null
     */
    public function priceInMinorUnits(float $min = 0.0, float $max = 1000.0, ?string $currencyCode = null): int;

    /**
     * Code, name and symbol of the local currency (LocaleInterface) - euro by default, e.g. ['code' => 'EUR',
     * 'name' => 'Euro', 'symbol' => '€']
     *
     * @return array{code: string, name: string, symbol: string}
     */
    public function localCurrency(): array;

    /**
     * Price in the local currency, formatted as written locally (LocaleInterface), e.g. "1,234.56 EUR"
     */
    public function localPrice(float $min = 0.0, float $max = 1000.0): string;

    /**
     * Amount in the local currency, formatted as written locally (LocaleInterface price pattern and separators), e.g.
     * formatPrice(1234.5) -> "1,234.50 EUR" by default, "1 234,50 zł" in a Polish pack
     */
    public function formatPrice(float $amount): string;

    /**
     * Number of decimals (ISO 4217 minor unit) of given currency - 2 for most, 0 for JPY, 3 for KWD
     */
    public function currencyDecimals(string $currencyCode): int;

    /**
     * Bank account number of $length digits (domestic account numbers have 8-17 in most countries), e.g. "4820193746"
     */
    public function bankAccountNumber(int $length = 10): string;

    /**
     * e.g. "deposit", "refund"
     */
    public function transactionType(): string;

    /**
     * Bitcoin mainnet address with valid checksum (random key - nobody owns it):
     * "p2pkh" (legacy, starts with 1), "p2sh" (starts with 3) or "bech32" (SegWit, starts with bc1q)
     */
    public function bitcoinAddress(?string $type = null): string;

    /**
     * Ethereum address - "0x" and 40 hex characters with EIP-55 mixed-case checksum, as wallets show it,
     * e.g. "0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed"; lowercase without checksum when $checksum is false
     */
    public function ethereumAddress(bool $checksum = true): string;
}
