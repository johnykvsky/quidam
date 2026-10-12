<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Psr\Clock\ClockInterface;
use Quidam\Definitions\Calculator\IbanCalculatorInterface;
use Quidam\Definitions\Calculator\LuhnCalculatorInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Payment implements PaymentExtensionInterface
{
    /**
     * Card brands: weight (how often picked), number length, prefix ranges, digit groups when formatted
     *
     * @var array<string, array{weight: int, length: int, prefixes: list<array{int, int}>, groups: list<int>}>
     *
     * @see https://en.wikipedia.org/wiki/Payment_card_number
     */
    protected const array CARDS = [
        'Visa' => ['weight' => 50, 'length' => 16, 'prefixes' => [[4, 4]], 'groups' => [4, 4, 4, 4]],
        'MasterCard' => ['weight' => 30, 'length' => 16, 'prefixes' => [[51, 55], [2221, 2720]], 'groups' => [4, 4, 4, 4]],
        'American Express' => ['weight' => 8, 'length' => 15, 'prefixes' => [[34, 34], [37, 37]], 'groups' => [4, 6, 5]],
        'Discover' => ['weight' => 4, 'length' => 16, 'prefixes' => [[6011, 6011], [644, 649], [65, 65]], 'groups' => [4, 4, 4, 4]],
        'JCB' => ['weight' => 3, 'length' => 16, 'prefixes' => [[3528, 3589]], 'groups' => [4, 4, 4, 4]],
        'UnionPay' => ['weight' => 3, 'length' => 16, 'prefixes' => [[62, 62]], 'groups' => [4, 4, 4, 4]],
        'Diners Club' => ['weight' => 2, 'length' => 14, 'prefixes' => [[300, 305], [36, 36], [38, 39]], 'groups' => [4, 6, 4]],
    ];

    /**
     * BBAN structure of countries using IBAN: length + class (n - digits, a - uppercase letters, c - alphanumeric)
     *
     * @var array<string, string>
     *
     * @see https://www.swift.com/standards/data-standards/iban
     */
    protected const array IBAN_FORMATS = [
        'AD' => '4n4n12c', 'AE' => '3n16n', 'AL' => '8n16c', 'AT' => '5n11n', 'AZ' => '4a20c', 'BA' => '3n3n8n2n',
        'BE' => '3n7n2n', 'BG' => '4a4n2n8c', 'BH' => '4a14c', 'BI' => '5n5n11n2n', 'BR' => '8n5n10n1a1c',
        'BY' => '4c4n16c', 'CH' => '5n12c', 'CR' => '4n14n', 'CY' => '3n5n16c', 'CZ' => '4n6n10n', 'DE' => '8n10n',
        'DJ' => '5n5n11n2n', 'DK' => '4n9n1n', 'DO' => '4c20n', 'EE' => '2n2n11n1n', 'EG' => '4n4n17n',
        'ES' => '4n4n1n1n10n', 'FI' => '6n7n1n', 'FK' => '2a12n', 'FO' => '4n9n1n', 'FR' => '5n5n11c2n',
        'GB' => '4a6n8n', 'GE' => '2a16n', 'GI' => '4a15c', 'GL' => '4n9n1n', 'GR' => '3n4n16c', 'GT' => '4c20c',
        'HN' => '4a20n', 'HR' => '7n10n', 'HU' => '3n4n1n15n1n', 'IE' => '4a6n8n', 'IL' => '3n3n13n', 'IQ' => '4a3n12n',
        'IS' => '4n2n6n10n', 'IT' => '1a5n5n12c', 'JO' => '4a4n18c', 'KW' => '4a22n', 'KZ' => '3n13c', 'LB' => '4n20c',
        'LC' => '4a24c', 'LI' => '5n12c', 'LT' => '5n11n', 'LU' => '3n13c', 'LV' => '4a13c', 'LY' => '3n3n15n',
        'MC' => '5n5n11c2n', 'MD' => '2c18c', 'ME' => '3n13n2n', 'MK' => '3n10c2n', 'MN' => '4n12n',
        'MR' => '5n5n11n2n', 'MT' => '4a5n18c', 'MU' => '4a2n2n12n3n3a', 'NI' => '4a20n', 'NL' => '4a10n',
        'NO' => '4n6n1n', 'OM' => '3n16c', 'PK' => '4a16c', 'PL' => '8n16n', 'PS' => '4a21c', 'PT' => '4n4n11n2n', 'QA' => '4a21c',
        'RO' => '4a16c', 'RS' => '3n13n2n', 'RU' => '9n5n15c', 'SA' => '2n18c', 'SC' => '4a2n2n16n3a', 'SD' => '2n12n',
        'SE' => '3n16n1n', 'SI' => '5n8n2n', 'SK' => '4n6n10n', 'SM' => '1a5n5n12c', 'SO' => '4n3n12n',
        'ST' => '4n4n11n2n', 'SV' => '4a20n', 'TL' => '3n14n2n', 'TN' => '2n3n13n2n', 'TR' => '5n1n16c',
        'UA' => '6n19c', 'VA' => '3n15n', 'VG' => '4a16n', 'XK' => '4n10n2n', 'YE' => '4a4n18c',
    ];

    /**
     * Circulating currencies (ISO 4217) - without precious metals, funds and testing codes
     *
     * @var array<string, string>
     *
     * @see https://www.iso.org/iso-4217-currency-codes.html
     */
    protected const array CURRENCIES = [
        'AED' => 'UAE Dirham',
        'AFN' => 'Afghani',
        'ALL' => 'Lek',
        'AMD' => 'Armenian Dram',
        'AOA' => 'Kwanza',
        'ARS' => 'Argentine Peso',
        'AUD' => 'Australian Dollar',
        'AWG' => 'Aruban Florin',
        'AZN' => 'Azerbaijan Manat',
        'BAM' => 'Convertible Mark',
        'BBD' => 'Barbados Dollar',
        'BDT' => 'Taka',
        'BHD' => 'Bahraini Dinar',
        'BIF' => 'Burundi Franc',
        'BMD' => 'Bermudian Dollar',
        'BND' => 'Brunei Dollar',
        'BOB' => 'Boliviano',
        'BRL' => 'Brazilian Real',
        'BSD' => 'Bahamian Dollar',
        'BTN' => 'Ngultrum',
        'BWP' => 'Pula',
        'BYN' => 'Belarusian Ruble',
        'BZD' => 'Belize Dollar',
        'CAD' => 'Canadian Dollar',
        'CDF' => 'Congolese Franc',
        'CHF' => 'Swiss Franc',
        'CLP' => 'Chilean Peso',
        'CNY' => 'Yuan Renminbi',
        'COP' => 'Colombian Peso',
        'CRC' => 'Costa Rican Colon',
        'CUP' => 'Cuban Peso',
        'CVE' => 'Cabo Verde Escudo',
        'CZK' => 'Czech Koruna',
        'DJF' => 'Djibouti Franc',
        'DKK' => 'Danish Krone',
        'DOP' => 'Dominican Peso',
        'DZD' => 'Algerian Dinar',
        'EGP' => 'Egyptian Pound',
        'ERN' => 'Nakfa',
        'ETB' => 'Ethiopian Birr',
        'EUR' => 'Euro',
        'FJD' => 'Fiji Dollar',
        'FKP' => 'Falkland Islands Pound',
        'GBP' => 'Pound Sterling',
        'GEL' => 'Lari',
        'GHS' => 'Ghana Cedi',
        'GIP' => 'Gibraltar Pound',
        'GMD' => 'Dalasi',
        'GNF' => 'Guinean Franc',
        'GTQ' => 'Quetzal',
        'GYD' => 'Guyana Dollar',
        'HKD' => 'Hong Kong Dollar',
        'HNL' => 'Lempira',
        'HTG' => 'Gourde',
        'HUF' => 'Forint',
        'IDR' => 'Rupiah',
        'ILS' => 'New Israeli Sheqel',
        'INR' => 'Indian Rupee',
        'IQD' => 'Iraqi Dinar',
        'IRR' => 'Iranian Rial',
        'ISK' => 'Iceland Krona',
        'JMD' => 'Jamaican Dollar',
        'JOD' => 'Jordanian Dinar',
        'JPY' => 'Yen',
        'KES' => 'Kenyan Shilling',
        'KGS' => 'Som',
        'KHR' => 'Riel',
        'KMF' => 'Comorian Franc',
        'KPW' => 'North Korean Won',
        'KRW' => 'Won',
        'KWD' => 'Kuwaiti Dinar',
        'KYD' => 'Cayman Islands Dollar',
        'KZT' => 'Tenge',
        'LAK' => 'Lao Kip',
        'LBP' => 'Lebanese Pound',
        'LKR' => 'Sri Lanka Rupee',
        'LRD' => 'Liberian Dollar',
        'LSL' => 'Loti',
        'LYD' => 'Libyan Dinar',
        'MAD' => 'Moroccan Dirham',
        'MDL' => 'Moldovan Leu',
        'MGA' => 'Malagasy Ariary',
        'MKD' => 'Denar',
        'MMK' => 'Kyat',
        'MNT' => 'Tugrik',
        'MOP' => 'Pataca',
        'MRU' => 'Ouguiya',
        'MUR' => 'Mauritius Rupee',
        'MVR' => 'Rufiyaa',
        'MWK' => 'Malawi Kwacha',
        'MXN' => 'Mexican Peso',
        'MYR' => 'Malaysian Ringgit',
        'MZN' => 'Mozambique Metical',
        'NAD' => 'Namibia Dollar',
        'NGN' => 'Naira',
        'NIO' => 'Cordoba Oro',
        'NOK' => 'Norwegian Krone',
        'NPR' => 'Nepalese Rupee',
        'NZD' => 'New Zealand Dollar',
        'OMR' => 'Rial Omani',
        'PAB' => 'Balboa',
        'PEN' => 'Sol',
        'PGK' => 'Kina',
        'PHP' => 'Philippine Peso',
        'PKR' => 'Pakistan Rupee',
        'PLN' => 'Zloty',
        'PYG' => 'Guarani',
        'QAR' => 'Qatari Rial',
        'RON' => 'Romanian Leu',
        'RSD' => 'Serbian Dinar',
        'RUB' => 'Russian Ruble',
        'RWF' => 'Rwanda Franc',
        'SAR' => 'Saudi Riyal',
        'SBD' => 'Solomon Islands Dollar',
        'SCR' => 'Seychelles Rupee',
        'SDG' => 'Sudanese Pound',
        'SEK' => 'Swedish Krona',
        'SGD' => 'Singapore Dollar',
        'SHP' => 'Saint Helena Pound',
        'SLE' => 'Leone',
        'SOS' => 'Somali Shilling',
        'SRD' => 'Surinam Dollar',
        'SSP' => 'South Sudanese Pound',
        'STN' => 'Dobra',
        'SYP' => 'Syrian Pound',
        'SZL' => 'Lilangeni',
        'THB' => 'Baht',
        'TJS' => 'Somoni',
        'TMT' => 'Turkmenistan New Manat',
        'TND' => 'Tunisian Dinar',
        'TOP' => 'Pa\'anga',
        'TRY' => 'Turkish Lira',
        'TTD' => 'Trinidad and Tobago Dollar',
        'TWD' => 'New Taiwan Dollar',
        'TZS' => 'Tanzanian Shilling',
        'UAH' => 'Hryvnia',
        'UGX' => 'Uganda Shilling',
        'USD' => 'US Dollar',
        'UYU' => 'Peso Uruguayo',
        'UZS' => 'Uzbekistan Sum',
        'VED' => 'Bolívar Soberano',
        'VES' => 'Bolívar Soberano',
        'VND' => 'Dong',
        'VUV' => 'Vatu',
        'WST' => 'Tala',
        'XAF' => 'CFA Franc BEAC',
        'XCD' => 'East Caribbean Dollar',
        'XCG' => 'Caribbean Guilder',
        'XOF' => 'CFA Franc BCEAO',
        'XPF' => 'CFP Franc',
        'YER' => 'Yemeni Rial',
        'ZAR' => 'Rand',
        'ZMW' => 'Zambian Kwacha',
        'ZWG' => 'Zimbabwe Gold',
    ];

    /**
     * Symbols of commonly used currencies, others use code as symbol
     *
     * @var array<string, string>
     */
    protected const array CURRENCY_SYMBOLS = [
        'ARS' => '$', 'AUD' => 'A$', 'AZN' => '₼', 'BDT' => '৳', 'BRL' => 'R$', 'CAD' => 'C$', 'CLP' => '$', 'CNY' => '¥',
        'COP' => '$', 'CZK' => 'Kč', 'DKK' => 'kr.', 'EUR' => '€', 'GBP' => '£', 'GEL' => '₾', 'HKD' => 'HK$', 'HUF' => 'Ft',
        'IDR' => 'Rp', 'ILS' => '₪', 'INR' => '₹', 'ISK' => 'kr', 'JPY' => '¥', 'KRW' => '₩', 'KZT' => '₸', 'MXN' => '$',
        'MYR' => 'RM', 'NGN' => '₦', 'NOK' => 'kr', 'NZD' => 'NZ$', 'PHP' => '₱', 'PLN' => 'zł', 'RON' => 'lei',
        'RUB' => '₽', 'SEK' => 'kr', 'SGD' => 'S$', 'THB' => '฿', 'TRY' => '₺', 'TWD' => 'NT$', 'UAH' => '₴', 'USD' => '$',
        'VND' => '₫', 'ZAR' => 'R',
    ];

    /**
     * ISO 4217 minor unit digits of currencies which don't use 2 decimals
     *
     * @var array<string, int>
     */
    protected const array CURRENCY_DECIMALS = [
        'BHD' => 3, 'BIF' => 0, 'CLP' => 0, 'DJF' => 0, 'GNF' => 0, 'IQD' => 3, 'ISK' => 0, 'JOD' => 3, 'JPY' => 0,
        'KMF' => 0, 'KRW' => 0, 'KWD' => 3, 'LYD' => 3, 'OMR' => 3, 'PYG' => 0, 'RWF' => 0, 'TND' => 3, 'UGX' => 0,
        'UYI' => 0, 'VND' => 0, 'VUV' => 0, 'XAF' => 0, 'XOF' => 0, 'XPF' => 0,
    ];

    /** @var list<string> */
    protected const array TRANSACTION_TYPES = [
        'deposit', 'withdrawal', 'payment', 'transfer', 'refund', 'invoice', 'fee', 'interest',
    ];

    private const string ALPHANUMERIC = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const string BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    private const string BECH32_CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

    private const array BECH32_GENERATOR = [0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3];

    /** Keccak-f[1600] round constants (as hex - most don't fit a signed int literal) */
    private const array KECCAK_ROUND_CONSTANTS = [
        '0000000000000001', '0000000000008082', '800000000000808a', '8000000080008000', '000000000000808b',
        '0000000080000001', '8000000080008081', '8000000000008009', '000000000000008a', '0000000000000088',
        '0000000080008009', '000000008000000a', '000000008000808b', '800000000000008b', '8000000000008089',
        '8000000000008003', '8000000000008002', '8000000000000080', '000000000000800a', '800000008000000a',
        '8000000080008081', '8000000000008080', '0000000080000001', '8000000080008008',
    ];

    /** Keccak rho rotation offsets, in pi lane order */
    private const array KECCAK_ROTATIONS = [1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 2, 14, 27, 41, 56, 8, 25, 43, 62, 18, 39, 61, 20, 44];

    /** Keccak pi lane order */
    private const array KECCAK_PI_LANES = [10, 7, 11, 17, 18, 3, 5, 16, 8, 21, 24, 4, 15, 23, 19, 13, 12, 2, 20, 14, 22, 9, 6, 1];

    /** Keccak-256 rate in bytes (1600 - 2 × 256 bits) */
    private const int KECCAK_256_RATE = 136;

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly StringsExtensionInterface $strings,
        protected readonly PersonExtensionInterface $person,
        protected readonly NumberExtensionInterface $number,
        protected readonly ClockInterface $clock,
        protected readonly LuhnCalculatorInterface $luhn,
        protected readonly IbanCalculatorInterface $ibanCalculator,
        protected readonly LocaleInterface $locale,
    ) {
    }

    /**
     * Card brand, picked by popularity, e.g. "Visa"
     */
    public function creditCardType(): string
    {
        return $this->randomizer->weightedElement(array_map(static fn (array $card): int => $card['weight'], static::CARDS));
    }

    /**
     * Card number with valid prefix, length and Luhn check digit, e.g. "4539148803436467" or formatted "4539-1488-0343-6467"
     *
     * @param string|null $type Card brand (see creditCardType()), random when null
     */
    public function creditCardNumber(?string $type = null, bool $formatted = false, string $separator = '-'): string
    {
        $card = $this->card($type ?? $this->creditCardType());

        [$from, $to] = $this->randomizer->randomElement($card['prefixes']);
        $number = (string) $this->randomizer->getInt($from, $to);
        $number .= $this->strings->numerify(str_repeat('#', $card['length'] - strlen($number) - 1));
        $number .= $this->luhn->checkDigit($number);

        return $formatted ? $this->groupCardNumber($number, $card['groups'], $separator) : $number;
    }

    /**
     * Card number with all but last 4 digits masked, grouped like on the card, e.g. "**** **** **** 6467"
     *
     * @param string|null $type Card brand (see creditCardType()), random when null
     */
    public function maskedCreditCardNumber(?string $type = null, string $maskCharacter = '*'): string
    {
        $type ??= $this->creditCardType();
        $number = $this->creditCardNumber($type);
        $masked = str_repeat($maskCharacter, strlen($number) - 4) . substr($number, -4);

        return $this->groupCardNumber($masked, $this->card($type)['groups'], ' ');
    }

    /**
     * Expiration month, e.g. "09/28". Valid one is in current month or up to 5 years ahead (clock "now"),
     * expired one is in last 3 years.
     */
    public function creditCardExpirationDate(bool $valid = true, string $format = 'm/y'): string
    {
        $months = $valid ? $this->randomizer->getInt(0, 60) : $this->randomizer->getInt(-36, -1);

        return $this->clock->now()->modify('first day of this month')->modify(sprintf('%+d months', $months))->format($format);
    }

    /**
     * Security code - 4 digits for American Express, 3 for others
     *
     * @param string|null $type Card brand (see creditCardType())
     */
    public function creditCardCvv(?string $type = null): string
    {
        if ($type !== null) {
            $this->card($type);
        }

        return $this->strings->numerify($type === 'American Express' ? '####' : '###');
    }

    /** @return array{type: string, number: string, name: string, expirationDate: string, cvv: string} */
    public function creditCardDetails(bool $valid = true): array
    {
        $type = $this->creditCardType();

        return [
            'type' => $type,
            'number' => $this->creditCardNumber($type),
            'name' => $this->person->name(),
            'expirationDate' => $this->creditCardExpirationDate($valid),
            'cvv' => $this->creditCardCvv($type),
        ];
    }

    /**
     * IBAN with valid length, structure and checksum, e.g. "PL61109010140000071219812874"
     * or formatted "PL61 1090 1014 0000 0712 1981 2874"
     *
     * @param string|null $countryCode ISO 3166-1 alpha-2 code of country using IBAN - when null the locale country
     *                                 (if it uses IBAN), otherwise random
     * @param string $prefix Beginning of the account number (BBAN), i.e. bank code
     */
    public function iban(?string $countryCode = null, string $prefix = '', bool $formatted = false): string
    {
        $localCountry = $this->locale->countryCode();
        $countryCode = strtoupper($countryCode ?? (isset(static::IBAN_FORMATS[(string) $localCountry]) ? (string) $localCountry : $this->randomizer->randomKey(static::IBAN_FORMATS)));

        if (!isset(static::IBAN_FORMATS[$countryCode])) {
            throw new InvalidArgumentException(sprintf('Country "%s" does not use IBAN', $countryCode));
        }

        $classes = $this->ibanClasses(static::IBAN_FORMATS[$countryCode]);
        $this->validateIbanPrefix($prefix, $classes);

        $bban = $prefix;
        foreach (str_split(substr($classes, strlen($prefix))) as $class) {
            $bban .= match ($class) {
                'n' => (string) $this->randomizer->getInt(0, 9),
                'a' => strtoupper($this->randomizer->randomLetter()),
                default => $this->randomizer->getBytesFromString(self::ALPHANUMERIC, 1),
            };
        }

        $iban = $countryCode . $this->ibanCalculator->checksum($countryCode, $bban) . $bban;

        return $formatted ? implode(' ', str_split($iban, 4)) : $iban;
    }

    /**
     * SWIFT / BIC code: 8 characters, or 11 with branch code, e.g. "DEUTDEFF" or "DEUTDEFF500"
     */
    public function swiftBicNumber(): string
    {
        $bic = strtoupper($this->strings->lexify('????'))
            . $this->randomizer->randomKey(static::IBAN_FORMATS)
            . strtoupper($this->randomizer->randomLetter())
            // second location character "0" means test BIC
            . $this->randomizer->getBytesFromString('ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789', 1);

        return match ($this->randomizer->getInt(0, 3)) {
            0, 1 => $bic,
            2 => $bic . 'XXX',
            // branch code can't start with "X", which is reserved for "XXX" (primary office)
            default => $bic . $this->randomizer->getBytesFromString('ABCDEFGHIJKLMNOPQRSTUVWYZ0123456789', 1)
                . $this->randomizer->getBytesFromString(self::ALPHANUMERIC, 2),
        };
    }

    /**
     * ISO 4217 code, e.g. "EUR"
     */
    public function currencyCode(): string
    {
        return $this->currencyData()['code'];
    }

    /**
     * e.g. "Pound Sterling"
     */
    public function currencyName(): string
    {
        return $this->currencyData()['name'];
    }

    /**
     * Symbol of one of commonly used currencies, e.g. "€"
     */
    public function currencySymbol(): string
    {
        return $this->randomizer->randomElement(static::CURRENCY_SYMBOLS);
    }

    /**
     * Matching code, name and symbol of one currency, e.g. ['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł'].
     * Currencies without common symbol use code as symbol.
     *
     * @return array{code: string, name: string, symbol: string}
     */
    public function currencyData(): array
    {
        $code = $this->randomizer->randomKey(static::CURRENCIES);

        return ['code' => $code, 'name' => static::CURRENCIES[$code], 'symbol' => static::CURRENCY_SYMBOLS[$code] ?? $code];
    }

    /**
     * Price within [$min, $max] with $decimals decimals
     */
    public function price(float $min = 0.0, float $max = 1000.0, int $decimals = 2): float
    {
        if ($min < 0.0) {
            throw new InvalidArgumentException('price() $min cannot be negative');
        }

        return $this->number->randomFloat($decimals, $min, $max);
    }

    /**
     * Price in minor units of the currency (cents), as integer - like most money libraries store it. Decimals follow
     * ISO 4217: price 12.34 is 1234 in USD, 12 in JPY (no minor unit), 12340 in KWD (3 decimals)
     *
     * @param string|null $currencyCode ISO 4217 code, see currencyDecimals() - the local currency (localCurrency()) when null
     */
    public function priceInMinorUnits(float $min = 0.0, float $max = 1000.0, ?string $currencyCode = null): int
    {
        $currencyCode ??= $this->locale->currencyCode();
        $factor = 10 ** $this->currencyDecimals($currencyCode);

        return (int) round($this->price($min, $max, $this->currencyDecimals($currencyCode)) * $factor);
    }

    /**
     * Code, name and symbol of the local currency (LocaleInterface) - euro by default, e.g. ['code' => 'EUR',
     * 'name' => 'Euro', 'symbol' => '€']
     *
     * @return array{code: string, name: string, symbol: string}
     */
    public function localCurrency(): array
    {
        $code = $this->locale->currencyCode();

        return ['code' => $code, 'name' => static::CURRENCIES[$code] ?? $code, 'symbol' => $this->locale->currencySymbol()];
    }

    /**
     * Price in the local currency, formatted as written locally (LocaleInterface), e.g. "1,234.56 EUR"
     */
    public function localPrice(float $min = 0.0, float $max = 1000.0): string
    {
        return $this->formatPrice($this->price($min, $max, $this->currencyDecimals($this->locale->currencyCode())));
    }

    /**
     * Amount in the local currency, formatted as written locally (LocaleInterface price pattern and separators), e.g.
     * formatPrice(1234.5) -> "1,234.50 EUR" by default, "1 234,50 zł" in a Polish pack
     */
    public function formatPrice(float $amount): string
    {
        $code = $this->locale->currencyCode();
        $number = number_format(abs($amount), $this->currencyDecimals($code), $this->locale->decimalSeparator(), $this->locale->thousandsSeparator());

        return ($amount < 0 ? '-' : '') . strtr($this->locale->pricePattern(), [
            '{amount}' => $number,
            '{symbol}' => $this->locale->currencySymbol(),
            '{code}' => $code,
        ]);
    }

    /**
     * Number of decimals (ISO 4217 minor unit) of given currency - 2 for most, 0 for JPY, 3 for KWD
     */
    public function currencyDecimals(string $currencyCode): int
    {
        $currencyCode = strtoupper($currencyCode);

        if (!isset(static::CURRENCIES[$currencyCode])) {
            throw new InvalidArgumentException(sprintf('Unknown currency code "%s"', $currencyCode));
        }

        return static::CURRENCY_DECIMALS[$currencyCode] ?? 2;
    }

    /**
     * Bank account number of $length digits (domestic account numbers have 8-17 in most countries), e.g. "4820193746"
     */
    public function bankAccountNumber(int $length = 10): string
    {
        if ($length < 4 || $length > 34) {
            throw new InvalidArgumentException('bankAccountNumber() $length must be between 4 and 34');
        }

        return $this->strings->numerify(str_repeat('#', $length));
    }

    /**
     * e.g. "deposit", "refund"
     */
    public function transactionType(): string
    {
        return $this->randomizer->randomElement(static::TRANSACTION_TYPES);
    }

    /**
     * Bitcoin mainnet address with valid checksum (random key - nobody owns it):
     * "p2pkh" (legacy, starts with 1), "p2sh" (starts with 3) or "bech32" (SegWit, starts with bc1q)
     */
    public function bitcoinAddress(?string $type = null): string
    {
        $type ??= $this->randomizer->randomElement(['p2pkh', 'p2sh', 'bech32']);
        $hash = $this->randomizer->getBytes(20);

        return match ($type) {
            'p2pkh' => $this->base58Check("\x00" . $hash),
            'p2sh' => $this->base58Check("\x05" . $hash),
            'bech32' => $this->bech32('bc', 0, $hash),
            default => throw new InvalidArgumentException(sprintf('Unknown bitcoin address type "%s", use p2pkh, p2sh or bech32', $type)),
        };
    }

    /**
     * Ethereum address - "0x" and 40 hex characters with EIP-55 mixed-case checksum, as wallets show it,
     * e.g. "0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed"; lowercase without checksum when $checksum is false
     */
    public function ethereumAddress(bool $checksum = true): string
    {
        $address = bin2hex($this->randomizer->getBytes(20));

        return '0x' . ($checksum ? $this->eip55Checksum($address) : $address);
    }

    /**
     * EIP-55: hex letter is uppercase when the matching nibble of Keccak-256 of the lowercase address is 8 or more
     */
    protected function eip55Checksum(string $address): string
    {
        $address = strtolower($address);
        $hash = bin2hex($this->keccak256($address));

        $result = '';
        foreach (str_split($address) as $position => $character) {
            $result .= ctype_alpha($character) && hexdec($hash[$position]) >= 8 ? strtoupper($character) : $character;
        }

        return $result;
    }

    /**
     * Keccak-256 (original Keccak padding, as used by Ethereum - not SHA3-256, which differs only in $domain: 0x06)
     */
    protected function keccak256(string $data, int $domain = 0x01): string
    {
        // pad: domain byte, zeros, 0x80 in the last byte of the block (both in one byte when only one is left)
        $padding = self::KECCAK_256_RATE - strlen($data) % self::KECCAK_256_RATE;
        $data .= $padding === 1 ? chr(($domain | 0x80) & 0xff) : chr($domain & 0xff) . str_repeat("\x00", $padding - 2) . "\x80";

        $state = array_fill(0, 25, 0);
        foreach (str_split($data, self::KECCAK_256_RATE) as $block) {
            // lanes are little-endian 64-bit words
            foreach (str_split($block, 8) as $lane => $bytes) {
                $state[$lane] ^= $this->bytesToInt(strrev($bytes));
            }

            $state = $this->keccakPermutation($state);
        }

        return substr(pack('P*', ...$state), 0, 32);
    }

    /**
     * Keccak-f[1600] permutation of 25 64-bit lanes
     *
     * @param array<int, int> $state
     * @return array<int, int>
     */
    protected function keccakPermutation(array $state): array
    {
        foreach (self::KECCAK_ROUND_CONSTANTS as $roundConstant) {
            // theta
            $columns = [];
            for ($x = 0; $x < 5; ++$x) {
                $columns[$x] = $state[$x] ^ $state[$x + 5] ^ $state[$x + 10] ^ $state[$x + 15] ^ $state[$x + 20];
            }

            for ($x = 0; $x < 5; ++$x) {
                $mix = $columns[($x + 4) % 5] ^ $this->rotateLeft($columns[($x + 1) % 5], 1);
                for ($y = 0; $y < 25; $y += 5) {
                    $state[$y + $x] ^= $mix;
                }
            }

            // rho and pi
            $current = $state[1];
            foreach (self::KECCAK_PI_LANES as $index => $lane) {
                $next = $state[$lane];
                $state[$lane] = $this->rotateLeft($current, self::KECCAK_ROTATIONS[$index]);
                $current = $next;
            }

            // chi
            for ($y = 0; $y < 25; $y += 5) {
                $row = array_slice($state, $y, 5);
                for ($x = 0; $x < 5; ++$x) {
                    $state[$y + $x] = $row[$x] ^ (~$row[($x + 1) % 5] & $row[($x + 2) % 5]);
                }
            }

            // iota
            $state[0] ^= $this->bytesToInt((string) hex2bin($roundConstant));
        }

        return $state;
    }

    /**
     * 64-bit rotation - PHP ints are signed, so the right shift is masked to drop copied sign bits
     */
    protected function rotateLeft(int $value, int $bits): int
    {
        return ($value << $bits) | (($value >> (64 - $bits)) & ((1 << $bits) - 1));
    }

    /**
     * Big-endian bytes as int - 8 bytes fill all 64 bits, the highest one becomes the sign bit
     */
    protected function bytesToInt(string $bytes): int
    {
        $value = 0;
        foreach (str_split($bytes) as $byte) {
            $value = ($value << 8) | ord($byte);
        }

        return $value;
    }

    /**
     * Splits card number into groups by brand layout, e.g. 4-4-4-4 or 4-6-5
     *
     * @param list<int> $groups
     */
    protected function groupCardNumber(string $number, array $groups, string $separator): string
    {
        $parts = [];
        $offset = 0;
        foreach ($groups as $length) {
            $parts[] = mb_substr($number, $offset, $length);
            $offset += $length;
        }

        return implode($separator, $parts);
    }

    /**
     * Base58Check: payload with first 4 bytes of double SHA-256 appended, in Base58 (leading zero bytes as "1")
     */
    protected function base58Check(string $payload): string
    {
        $data = $payload . substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        $digits = array_map(ord(...), str_split($data));
        $encoded = '';

        // repeated division of the big-endian number by 58
        while ($digits !== []) {
            $remainder = 0;
            $quotient = [];
            foreach ($digits as $digit) {
                $accumulator = $remainder * 256 + $digit;
                $remainder = $accumulator % 58;
                if ($quotient !== [] || $accumulator >= 58) {
                    $quotient[] = intdiv($accumulator, 58);
                }
            }

            $encoded = self::BASE58_ALPHABET[$remainder] . $encoded;
            $digits = $quotient;
        }

        return str_repeat('1', strspn($data, "\x00")) . $encoded;
    }

    /**
     * Bech32 (BIP-173) SegWit address of given witness version and program
     */
    protected function bech32(string $hrp, int $witnessVersion, string $program): string
    {
        $data = [$witnessVersion, ...$this->convertBits(array_map(ord(...), str_split($program)), 8, 5)];
        $expanded = [...array_map(static fn (string $c): int => ord($c) >> 5, str_split($hrp)), 0, ...array_map(static fn (string $c): int => ord($c) & 31, str_split($hrp))];
        $polymod = $this->bech32Polymod([...$expanded, ...$data, 0, 0, 0, 0, 0, 0]) ^ 1;

        for ($i = 0; $i < 6; ++$i) {
            $data[] = ($polymod >> 5 * (5 - $i)) & 31;
        }

        return $hrp . '1' . implode('', array_map(static fn (int $value): string => self::BECH32_CHARSET[$value], $data));
    }

    /** @param list<int> $values */
    protected function bech32Polymod(array $values): int
    {
        $checksum = 1;
        foreach ($values as $value) {
            $top = $checksum >> 25;
            $checksum = (($checksum & 0x1ffffff) << 5) ^ $value;
            for ($i = 0; $i < 5; ++$i) {
                if ((($top >> $i) & 1) === 1) {
                    $checksum ^= self::BECH32_GENERATOR[$i];
                }
            }
        }

        return $checksum;
    }

    /**
     * Regroups bits, i.e. bytes (8 bits) into 5-bit groups, last group padded with zeros
     *
     * @param list<int> $values
     * @return list<int>
     */
    protected function convertBits(array $values, int $from, int $to): array
    {
        $accumulator = 0;
        $bits = 0;
        $result = [];
        $max = (1 << $to) - 1;

        foreach ($values as $value) {
            $accumulator = ($accumulator << $from) | $value;
            $bits += $from;
            while ($bits >= $to) {
                $bits -= $to;
                $result[] = ($accumulator >> $bits) & $max;
            }
        }

        if ($bits > 0) {
            $result[] = ($accumulator << ($to - $bits)) & $max;
        }

        return $result;
    }

    /** @return array{weight: int, length: int, prefixes: list<array{int, int}>, groups: list<int>} */
    protected function card(string $type): array
    {
        return static::CARDS[$type] ?? throw new InvalidArgumentException(sprintf(
            'Unknown card type "%s", use one of: %s',
            $type,
            implode(', ', array_keys(static::CARDS)),
        ));
    }

    /**
     * Expands "4a14n" to "aaaannnnnnnnnnnnnn"
     */
    protected function ibanClasses(string $format): string
    {
        preg_match_all('/(\d+)([nac])/', $format, $parts, PREG_SET_ORDER);

        return implode('', array_map(static fn (array $part): string => str_repeat($part[2], (int) $part[1]), $parts));
    }

    protected function validateIbanPrefix(string $prefix, string $classes): void
    {
        if (strlen($prefix) > strlen($classes)) {
            throw new InvalidArgumentException(sprintf('IBAN prefix "%s" is longer than account number (%d)', $prefix, strlen($classes)));
        }

        foreach (str_split($prefix) as $position => $character) {
            $valid = match ($classes[$position]) {
                'n' => ctype_digit($character),
                'a' => ctype_upper($character),
                default => ctype_upper($character) || ctype_digit($character),
            };

            if (!$valid) {
                throw new InvalidArgumentException(sprintf(
                    'IBAN prefix "%s" does not match account number format at position %d',
                    $prefix,
                    $position + 1,
                ));
            }
        }
    }
}
