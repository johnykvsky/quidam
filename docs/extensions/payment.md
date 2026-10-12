# Payment

`$generator->payment()` - interface `Quidam\Definitions\Extension\PaymentExtensionInterface`, default implementation `Quidam\Core\Payment`

Credit cards, IBAN, SWIFT / BIC, bank accounts, crypto addresses, currencies and prices. Numbers pass validation (Luhn, IBAN, ABA, Base58Check / Bech32 checksums), but they are not real accounts, cards or wallets.

## Methods

| Method | Returns | Example |
|---|---|---|
| `creditCardType()` | `string` - brand picked by popularity | `'Visa'` |
| `creditCardNumber(?string $type = null, bool $formatted = false, string $separator = '-')` | `string` | `'5377330388586609'`, `'3786-828973-18563'` |
| `maskedCreditCardNumber(?string $type = null, string $maskCharacter = '*')` | `string` - last 4 digits visible, grouped by brand | `'**** **** **** 6738'`, Amex `'**** ****** *0717'` |
| `creditCardExpirationDate(bool $valid = true, string $format = 'm/y')` | `string` | `'07/28'`, expired `'12/24'` |
| `creditCardCvv(?string $type = null)` | `string` - 4 digits for Amex, 3 for others | `'949'` |
| `creditCardDetails(bool $valid = true)` | `array{type, number, name, expirationDate, cvv}` | see below |
| `iban(?string $countryCode = null, string $prefix = '', bool $formatted = false)` | `string` - country of the [locale](../locale.md) when null (if it uses IBAN), otherwise random | `'PL77951403238116178872306458'`, `'DE94 3704 0044 8669 4927 75'` |
| `swiftBicNumber()` | `string` - 8 or 11 characters | `'NFOHMCZ2XXX'` |
| `bankAccountNumber(int $length = 10)` | `string` - digits | `'1419082202'` |
| `transactionType()` | `string` | `'deposit'`, `'refund'` |
| `bitcoinAddress(?string $type = null)` | `string` - `p2pkh`, `p2sh` or `bech32`, valid checksum | `'16Ys7i7TBBeo2uENnMjP7VxJjfyCXvhoXM'`, `'bc1qcfs4zmq9ugx07guq9nzuu7avt4nwcj69sk82nf'` |
| `ethereumAddress(bool $checksum = true)` | `string` - `0x` + 40 hex, EIP-55 mixed case | `'0xB27ab380e755702D5AA275d696987C78a6cf4811'`, `ethereumAddress(false)` → lowercase |
| `currencyCode()` | `string` - ISO 4217 | `'ALL'` |
| `currencyName()` | `string` | `'Hong Kong Dollar'` |
| `currencySymbol()` | `string` - symbol of commonly used currency | `'€'` |
| `currencyData()` | `array{code, name, symbol}` - matching values | `['code' => 'KZT', 'name' => 'Tenge', 'symbol' => '₸']` |
| `price(float $min = 0.0, float $max = 1000.0, int $decimals = 2)` | `float` | `782.08`, `price(5, 50)` → `35.6` |
| `priceInMinorUnits(float $min = 0.0, float $max = 1000.0, ?string $currencyCode = null)` | `int` - cents (ISO 4217 decimals), local currency when null | `priceInMinorUnits(10, 50)` → `3960`, `'JPY'` → `2954` |
| `localCurrency()` | `array{code, name, symbol}` - of the [locale](../locale.md), euro by default | `['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€']` |
| `localPrice(float $min = 0.0, float $max = 1000.0)` | `string` - price in the local currency, as written locally | `'501.56 EUR'` |
| `formatPrice(float $amount)` | `string` - amount in the local currency, as written locally | `formatPrice(1234.5)` → `'1,234.50 EUR'` |
| `currencyDecimals(string $currencyCode)` | `int` - ISO 4217 minor unit digits | `'EUR'` → `2`, `'JPY'` → `0`, `'KWD'` → `3` |

## Examples

```php
$payment = $generator->payment();

$payment->creditCardNumber('American Express', true);     // '3786-828973-18563'
$payment->creditCardNumber('Visa', true, ' ');            // '4871 8123 1708 1814'

$payment->creditCardDetails();
// ['type' => 'Visa', 'number' => '4329815984576873', 'name' => 'Stella Baker', 'expirationDate' => '07/27', 'cvv' => '972']

$payment->creditCardDetails(valid: false);                // expirationDate in the past - for "card expired" tests

$payment->iban('PL');                                     // 'PL77951403238116178872306458'
$payment->iban('DE', '37040044', true);                   // 'DE94 3704 0044 8669 4927 75' - with bank code

$product = [
    'price' => $payment->price(5, 50),
    'currency' => $payment->currencyCode(),
];
```

## Credit cards

| Type | Share | Length | Prefixes | Formatted |
|---|---|---|---|---|
| `Visa` | 50% | 16 | 4 | 4-4-4-4 |
| `MasterCard` | 30% | 16 | 51-55, 2221-2720 | 4-4-4-4 |
| `American Express` | 8% | 15 | 34, 37 | 4-6-5 |
| `Discover` | 4% | 16 | 6011, 644-649, 65 | 4-4-4-4 |
| `JCB` | 3% | 16 | 3528-3589 | 4-4-4-4 |
| `UnionPay` | 3% | 16 | 62 | 4-4-4-4 |
| `Diners Club` | 2% | 14 | 300-305, 36, 38-39 | 4-6-4 |

* every number has valid Luhn check digit
* expiration (clock based): valid - current month up to 5 years ahead, expired (`valid: false`) - last 3 years
* unknown type throws (lists available types)

## IBAN

* 89 countries using IBAN, with registry structure (length, digit / letter / alphanumeric positions) and valid ISO 7064 mod 97-10 checksum
* country code is case insensitive, country without IBAN (i.e. `US`) throws
* `$prefix` sets beginning of the account number (BBAN), i.e. bank code - it's validated against country structure
* `$formatted` adds spaces every 4 characters (print format)

## Currencies

* 154 circulating ISO 4217 currencies - without precious metals (`XAU`), testing (`XTS`, `XXX`) and fund codes (`BOV`, `USN`...)
* `currencySymbol()` picks from 41 commonly used currencies with known symbol; in `currencyData()` currencies without such symbol use code as symbol (`CHF`)

## Bank accounts

* `bankAccountNumber()` - digits only, 4-34 (domestic account numbers have 8-17 in most countries); for countries using IBAN prefer `iban()`
* US ABA routing numbers are not in the core - they're US-only, so they belong in a US language pack (none is included)
* `maskedCreditCardNumber()` - a valid card number with all but the last 4 digits masked, grouped like the brand prints it - for "card ending in 1234" screens and receipts

## Money as integers

Most money libraries (and database columns) store amounts as integers in minor units. `priceInMinorUnits()` uses the currency's ISO 4217 decimals:

```php
$payment->priceInMinorUnits(10, 50);            // 3960 - 39.60 EUR
$payment->priceInMinorUnits(1000, 5000, 'JPY'); // 2954 - ¥2954, yen has no minor unit
$payment->priceInMinorUnits(1, 2, 'KWD');       // 1384 - 1.384 KWD, 3 decimals

$code = $payment->currencyCode();
$order = ['currency' => $code, 'total' => $payment->priceInMinorUnits(5, 500, $code)];
```

Override `CURRENCY_DECIMALS` to change exponents (currencies not listed use 2). Without a currency code the local currency is used.

## Local currency and prices

The local currency and the way prices are written come from the [locale](../locale.md) - `currencyCode()`, `currencySymbol()`, `decimalSeparator()`, `thousandsSeparator()` and `pricePattern()` (`{amount}`, `{symbol}`, `{code}` placeholders). Default is euro written neutrally - `'1,234.50 EUR'` (non-breaking space before the code). The currency name in `localCurrency()` comes from `CURRENCIES` (English names).

```php
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Locale\LocaleInterface;

$pl = $generator->withDefinition(LocaleInterface::class, new Locale(
    currencyCode: 'PLN',
    currencySymbol: 'zł',
    decimalSeparator: ',',
    thousandsSeparator: "\u{00A0}",
    pricePattern: "{amount}\u{00A0}{symbol}",
));

$pl->payment()->formatPrice(1234.5);   // '1 234,50 zł'
$pl->payment()->localCurrency();       // ['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł']
```

US style is `currencyCode: 'USD', currencySymbol: '$', pricePattern: '{symbol}{amount}'` → `'$1,234.50'`.

## Crypto

* `bitcoinAddress()`: mainnet `p2pkh` (legacy, `1...`, Base58Check), `p2sh` (`3...`, Base58Check) or `bech32` (native SegWit P2WPKH, `bc1q...`, BIP-173) - all pass checksum validation. Keys are random bytes: nobody owns these addresses, never send funds to them
* `ethereumAddress()`: EIP-55 checksum - letter case encodes a checksum, as wallets show addresses. It needs Keccak-256, which PHP doesn't have (`sha3-256` uses different padding), so Quidam implements it in PHP - verified against the EIP-55 vectors and, with SHA-3 padding, against PHP's `sha3-256`. `ethereumAddress(false)` gives lowercase (also valid, no checksum)
* encodings are protected methods (`base58Check()`, `bech32()`, `eip55Checksum()`, `keccak256()`); card and IBAN check digits come from calculator services - see [Calculators](../extending.md#calculators)

## SWIFT / BIC

`AAAA` bank + country + location (+ branch): 8 characters, or 11 with `XXX` (primary office) or branch code. Test BICs (location second character `0`) are not generated.
