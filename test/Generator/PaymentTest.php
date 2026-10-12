<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Core\Payment;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FrozenClock;
use ReflectionMethod;

final class PaymentTest extends GeneratorTestCase
{
    private const array CARD_PATTERNS = [
        'Visa' => '/^4\d{15}$/',
        'MasterCard' => '/^(5[1-5]\d{14}|2(22[1-9]|2[3-9]\d|[3-6]\d\d|7[01]\d|720)\d{12})$/',
        'American Express' => '/^3[47]\d{13}$/',
        'Discover' => '/^(6011\d{12}|64[4-9]\d{13}|65\d{14})$/',
        'JCB' => '/^35(2[89]|[3-8]\d)\d{12}$/',
        'UnionPay' => '/^62\d{14}$/',
        'Diners Club' => '/^(30[0-5]\d{11}|3[689]\d{12})$/',
    ];

    private PaymentExtensionInterface $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2026-06-15')))->ext(PaymentExtensionInterface::class);
    }

    /** @return iterable<string, array{string}> */
    public static function cardTypes(): iterable
    {
        foreach (array_keys(self::CARD_PATTERNS) as $type) {
            yield $type => [$type];
        }
    }

    #[DataProvider('cardTypes')]
    public function testCreditCardNumber(string $type): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $number = $this->payment->creditCardNumber($type);
            self::assertMatchesRegularExpression(self::CARD_PATTERNS[$type], $number);
            self::assertTrue(self::isLuhnValid($number), $number);
        }
    }

    public function testCreditCardNumberFormatting(): void
    {
        self::assertMatchesRegularExpression('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $this->payment->creditCardNumber('Visa', true));
        self::assertMatchesRegularExpression('/^\d{4} \d{6} \d{5}$/', $this->payment->creditCardNumber('American Express', true, ' '));
        self::assertMatchesRegularExpression('/^\d{4}-\d{6}-\d{4}$/', $this->payment->creditCardNumber('Diners Club', true));
    }

    public function testCreditCardTypeIsWeighted(): void
    {
        $types = [];
        for ($i = 0; $i < 2000; ++$i) {
            $type = $this->payment->creditCardType();
            $types[$type] = ($types[$type] ?? 0) + 1;
            self::assertArrayHasKey($type, self::CARD_PATTERNS);
        }

        self::assertGreaterThan($types['MasterCard'], $types['Visa']);
        self::assertGreaterThan($types['Diners Club'] ?? 0, $types['MasterCard']);
        $this->assertAlwaysMatches('/^\d+$/', fn (): string => $this->payment->creditCardNumber());
    }

    public function testExpirationDate(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $valid = DateTimeImmutable::createFromFormat('!m/y', $this->payment->creditCardExpirationDate());
            self::assertNotFalse($valid);
            self::assertGreaterThanOrEqual(new DateTimeImmutable('2026-06-01'), $valid);
            self::assertLessThanOrEqual(new DateTimeImmutable('2031-06-01'), $valid);

            $expired = DateTimeImmutable::createFromFormat('!m/y', $this->payment->creditCardExpirationDate(false));
            self::assertNotFalse($expired);
            self::assertLessThan(new DateTimeImmutable('2026-06-01'), $expired);
            self::assertGreaterThanOrEqual(new DateTimeImmutable('2023-06-01'), $expired);
        }

        self::assertMatchesRegularExpression('/^20\d{2}-\d{2}$/', $this->payment->creditCardExpirationDate(format: 'Y-m'));
    }

    public function testCvvAndDetails(): void
    {
        $this->assertAlwaysMatches('/^\d{4}$/', fn (): string => $this->payment->creditCardCvv('American Express'));
        $this->assertAlwaysMatches('/^\d{3}$/', fn (): string => $this->payment->creditCardCvv('Visa'));
        $this->assertAlwaysMatches('/^\d{3}$/', fn (): string => $this->payment->creditCardCvv());

        $details = $this->payment->creditCardDetails();
        self::assertMatchesRegularExpression(self::CARD_PATTERNS[$details['type']], $details['number']);
        self::assertSame($details['type'] === 'American Express' ? 4 : 3, strlen($details['cvv']));
        self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][A-Za-z]+$/', $details['name']);
    }

    public function testIban(): void
    {
        for ($i = 0; $i < 300; ++$i) {
            $iban = $this->payment->iban();
            self::assertMatchesRegularExpression('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban);
            self::assertTrue(self::isIbanValid($iban), $iban);
        }
    }

    /** @return iterable<string, array{non-empty-string, int}> */
    public static function ibanLengths(): iterable
    {
        yield 'Poland' => ['PL', 28];
        yield 'Germany' => ['DE', 22];
        yield 'Norway' => ['NO', 15];
        yield 'Saint Lucia' => ['LC', 32];
        yield 'Russia' => ['RU', 33];
        yield 'United Kingdom' => ['GB', 22];
    }

    /** @param non-empty-string $countryCode */
    #[DataProvider('ibanLengths')]
    public function testIbanForCountry(string $countryCode, int $length): void
    {
        $iban = $this->payment->iban(strtolower($countryCode));

        self::assertStringStartsWith($countryCode, $iban);
        self::assertSame($length, strlen($iban));
        self::assertTrue(self::isIbanValid($iban), $iban);
    }

    public function testIbanWithPrefixAndFormatting(): void
    {
        $iban = $this->payment->iban('DE', '10010010');
        self::assertSame('10010010', substr($iban, 4, 8));
        self::assertTrue(self::isIbanValid($iban));

        $formatted = $this->payment->iban('PL', formatted: true);
        self::assertMatchesRegularExpression('/^PL\d{2}( \d{4}){6}$/', $formatted);
        self::assertTrue(self::isIbanValid(str_replace(' ', '', $formatted)));
    }

    /** @return iterable<string, array{callable(PaymentExtensionInterface): mixed}> */
    public static function invalidCalls(): iterable
    {
        yield 'unknown card type' => [static fn (PaymentExtensionInterface $p): string => $p->creditCardNumber('Visa Retired')];
        yield 'unknown cvv type' => [static fn (PaymentExtensionInterface $p): string => $p->creditCardCvv('Unknown')];
        yield 'country without iban' => [static fn (PaymentExtensionInterface $p): string => $p->iban('US')];
        yield 'iban prefix too long' => [static fn (PaymentExtensionInterface $p): string => $p->iban('NO', '123456789012')];
        yield 'iban prefix wrong class' => [static fn (PaymentExtensionInterface $p): string => $p->iban('GB', '1234')];
        yield 'negative price' => [static fn (PaymentExtensionInterface $p): float => $p->price(-1.0)];
    }

    /** @param callable(PaymentExtensionInterface): mixed $call */
    #[DataProvider('invalidCalls')]
    public function testInvalidArguments(callable $call): void
    {
        $this->expectException(InvalidArgumentException::class);
        $call($this->payment);
    }

    public function testSwiftBic(): void
    {
        $this->assertAlwaysMatches('/^[A-Z]{4}[A-Z]{2}[A-Z][A-Z1-9]([A-WYZ0-9][A-Z0-9]{2}|XXX)?$/', fn (): string => $this->payment->swiftBicNumber());
    }

    public function testCurrency(): void
    {
        $this->assertAlwaysMatches('/^[A-Z]{3}$/', fn (): string => $this->payment->currencyCode());
        $this->assertAlwaysMatches('/^\S.+$/u', fn (): string => $this->payment->currencyName());
        $this->assertAlwaysMatches('/^\S+$/u', fn (): string => $this->payment->currencySymbol());

        $codes = [];
        for ($i = 0; $i < 2000; ++$i) {
            $currency = $this->payment->currencyData();
            self::assertSame($currency, $codes[$currency['code']] ?? $currency, 'same code always has same data');
            $codes[$currency['code']] = $currency;
        }

        foreach (['XAU', 'XXX', 'XTS', 'BOV', 'USN'] as $notCurrency) {
            self::assertArrayNotHasKey($notCurrency, $codes);
        }

        self::assertSame(['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł'], $codes['PLN'] ?? ['code' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł']);
    }

    public function testPrice(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $price = $this->payment->price(9.99, 10.01);
            self::assertContains($price, [9.99, 10.0, 10.01]);
        }
    }

    private static function isLuhnValid(string $number): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($number)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 1 ? 2 : 1);
            $sum += intdiv($value, 10) + $value % 10;
        }

        return $sum % 10 === 0;
    }

    private static function isIbanValid(string $iban): bool
    {
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $remainder = 0;

        foreach (str_split($rearranged) as $character) {
            $value = ctype_digit($character) ? $character : (string) (ord($character) - 55);
            foreach (str_split($value) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }

    public function testMaskedCreditCardNumber(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);

        $this->assertAlwaysMatches('/^\*{4} \*{4} \*{4} \d{4}$/', static fn (): string => $payment->maskedCreditCardNumber('Visa'));
        $this->assertAlwaysMatches('/^\*{4} \*{6} \*\d{4}$/', static fn (): string => $payment->maskedCreditCardNumber('American Express'));
        $this->assertAlwaysMatches('/^(•+ ?)+\d{4}$/u', static fn (): string => $payment->maskedCreditCardNumber(null, '•'));
    }

    public function testBankAccountNumber(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);

        $this->assertAlwaysMatches('/^\d{10}$/', static fn (): string => $payment->bankAccountNumber());
        $this->assertAlwaysMatches('/^\d{17}$/', static fn (): string => $payment->bankAccountNumber(17));

        $this->assertAlwaysMatches('/^[a-z]+$/', $payment->transactionType(...));

        $this->expectException(InvalidArgumentException::class);
        $payment->bankAccountNumber(3);
    }

    public function testPriceInMinorUnits(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);

        self::assertSame(2, $payment->currencyDecimals('usd'));
        self::assertSame(0, $payment->currencyDecimals('JPY'));
        self::assertSame(3, $payment->currencyDecimals('KWD'));

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $cents = $payment->priceInMinorUnits(1.5, 2.5);
            self::assertGreaterThanOrEqual(150, $cents);
            self::assertLessThanOrEqual(250, $cents);

            $yen = $payment->priceInMinorUnits(100, 200, 'JPY');
            self::assertGreaterThanOrEqual(100, $yen);
            self::assertLessThanOrEqual(200, $yen);

            $fils = $payment->priceInMinorUnits(1, 2, 'KWD');
            self::assertGreaterThanOrEqual(1000, $fils);
            self::assertLessThanOrEqual(2000, $fils);
        }

        $this->expectException(InvalidArgumentException::class);
        $payment->currencyDecimals('XXX');
    }

    public function testBitcoinAddressesHaveValidChecksums(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $legacy = $payment->bitcoinAddress('p2pkh');
            self::assertStringStartsWith('1', $legacy);
            self::assertTrue(self::validBase58Check($legacy, 0x00), $legacy);

            $script = $payment->bitcoinAddress('p2sh');
            self::assertStringStartsWith('3', $script);
            self::assertTrue(self::validBase58Check($script, 0x05), $script);

            $segwit = $payment->bitcoinAddress('bech32');
            self::assertMatchesRegularExpression('/^bc1q[02-9ac-hj-np-z]{38}$/', $segwit);
            self::assertTrue(self::validBech32($segwit), $segwit);

            self::assertMatchesRegularExpression('/^(1|3|bc1q)/', $payment->bitcoinAddress());
        }

        $this->expectException(InvalidArgumentException::class);
        $payment->bitcoinAddress('taproot');
    }

    public function testBitcoinEncodingTestVectors(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);

        // all-zero hash160 - well known "burn" address
        self::assertSame('1111111111111111111114oLvT2', new ReflectionMethod(Payment::class, 'base58Check')->invoke($payment, str_repeat("\0", 21)));
        // BIP-173 test vector
        self::assertSame(
            'bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4',
            new ReflectionMethod(Payment::class, 'bech32')->invoke($payment, 'bc', 0, (string) hex2bin('751e76e8199196d454941c45d1b3a323f1433bd6')),
        );
    }

    private static function validBase58Check(string $address, int $version): bool
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $bytes = array_fill(0, 25, 0);

        foreach (str_split($address) as $character) {
            $carry = (int) strpos($alphabet, $character);
            for ($i = 24; $i >= 0; --$i) {
                $carry += 58 * $bytes[$i];
                $bytes[$i] = $carry % 256;
                $carry = intdiv($carry, 256);
            }
        }

        $binary = pack('C*', ...$bytes);

        return $bytes[0] === $version
            && substr(hash('sha256', hash('sha256', substr($binary, 0, 21), true), true), 0, 4) === substr($binary, 21);
    }

    private static function validBech32(string $address): bool
    {
        $charset = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
        [$hrp, $data] = explode('1', $address, 2);
        $values = [...array_map(static fn (string $c): int => ord($c) >> 5, str_split($hrp)), 0, ...array_map(static fn (string $c): int => ord($c) & 31, str_split($hrp))];

        foreach (str_split($data) as $character) {
            $values[] = (int) strpos($charset, $character);
        }

        $checksum = 1;
        foreach ($values as $value) {
            $top = $checksum >> 25;
            $checksum = (($checksum & 0x1ffffff) << 5) ^ $value;
            foreach ([0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3] as $i => $generator) {
                $checksum ^= (($top >> $i) & 1) === 1 ? $generator : 0;
            }
        }

        return $checksum === 1;
    }

    public function testEthereumAddressHasEip55Checksum(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);
        $checksum = new ReflectionMethod(Payment::class, 'eip55Checksum');

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $address = $payment->ethereumAddress();
            self::assertMatchesRegularExpression('/^0x[0-9a-fA-F]{40}$/', $address);
            self::assertSame(substr($address, 2), $checksum->invoke($payment, strtolower(substr($address, 2))), 'checksum is stable');
            self::assertMatchesRegularExpression('/^0x[0-9a-f]{40}$/', $payment->ethereumAddress(false));
        }

        // EIP-55 test vectors
        foreach (['5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed', 'fB6916095ca1df60bB79Ce92cE3Ea74c37c5d359', 'dbF03B407c01E7cD3CBea99509d93f8DDDC8C6FB', 'D1220A0cf47c7B9Be7A2E6BA89F429762e7b9aDb'] as $vector) {
            self::assertSame($vector, $checksum->invoke($payment, strtolower($vector)));
        }
    }

    public function testKeccak256(): void
    {
        $payment = $this->dummy->ext(PaymentExtensionInterface::class);
        $keccak = new ReflectionMethod(Payment::class, 'keccak256');

        $hash = static fn (string $data, int $domain = 0x01): string => bin2hex(is_string($result = $keccak->invoke($payment, $data, $domain)) ? $result : '');

        self::assertSame('c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470', $hash(''));
        self::assertSame('4e03657aea45a94fc7d47ba826c8d667c0d1e6e33a64a036ec44f58fa12d6c45', $hash('abc'));

        // the same permutation with SHA-3 padding must match PHP's sha3-256 - every length around block boundaries
        foreach ([...range(0, 4), ...range(130, 140), ...range(268, 276)] as $length) {
            $data = str_repeat('x', $length);
            self::assertSame(hash('sha3-256', $data), $hash($data, 0x06), 'length ' . $length);
        }
    }

    public function testLocalCurrencyAndPrices(): void
    {
        self::assertSame(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'], $this->payment->localCurrency());
        self::assertSame("1,234,567.50\u{00A0}EUR", $this->payment->formatPrice(1234567.5));
        self::assertSame("-0.99\u{00A0}EUR", $this->payment->formatPrice(-0.99));
        $this->assertAlwaysMatches('/^\d{2}\.\d{2}\x{00A0}EUR$/u', fn (): string => $this->payment->localPrice(10, 99));

        self::assertSame(
            Quidam::create(seed: 3)->ext(PaymentExtensionInterface::class)->priceInMinorUnits(1, 9, 'EUR'),
            Quidam::create(seed: 3)->ext(PaymentExtensionInterface::class)->priceInMinorUnits(1, 9),
            'local currency by default',
        );
    }
}
