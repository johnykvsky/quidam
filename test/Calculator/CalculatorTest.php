<?php

declare(strict_types = 1);

namespace Quidam\Test\Calculator;

use PHPUnit\Framework\TestCase;
use Quidam\Core\Calculator\EanCalculator;
use Quidam\Core\Calculator\IbanCalculator;
use Quidam\Core\Calculator\IsbnCalculator;
use Quidam\Core\Calculator\LuhnCalculator;
use Quidam\Exception\InvalidArgumentException;

final class CalculatorTest extends TestCase
{
    public function testLuhn(): void
    {
        $luhn = new LuhnCalculator();

        self::assertSame('7', $luhn->checkDigit('453914880343646'));
        self::assertTrue($luhn->isValid('4539148803436467'));
        self::assertTrue($luhn->isValid('356938035643809'));
        self::assertFalse($luhn->isValid('4539148803436468'));
        self::assertFalse($luhn->isValid('7'));
        self::assertFalse($luhn->isValid('45391488x3436467'));
    }

    public function testEan(): void
    {
        $ean = new EanCalculator();

        self::assertSame('7', $ean->checkDigit('590123412345'));
        self::assertTrue($ean->isValid('5901234123457'));
        self::assertTrue($ean->isValid('96385074'));
        self::assertTrue($ean->isValid('9780306406157'));
        self::assertFalse($ean->isValid('5901234123458'));
        self::assertFalse($ean->isValid(''));
    }

    public function testIsbn(): void
    {
        $isbn = new IsbnCalculator();

        self::assertSame('2', $isbn->checkDigit('030640615'));
        self::assertSame('X', $isbn->checkDigit('080442957'));
        self::assertTrue($isbn->isValid('0306406152'));
        self::assertTrue($isbn->isValid('080442957X'));
        self::assertFalse($isbn->isValid('0306406153'));
        self::assertFalse($isbn->isValid('03064061'));

        $this->expectException(InvalidArgumentException::class);
        $isbn->checkDigit('12345');
    }

    public function testIban(): void
    {
        $iban = new IbanCalculator();

        self::assertSame('61', $iban->checksum('PL', '109010140000071219812874'));
        self::assertSame('82', $iban->checksum('GB', 'WEST12345698765432'));
        self::assertTrue($iban->isValid('PL61109010140000071219812874'));
        self::assertTrue($iban->isValid('GB82WEST12345698765432'));
        self::assertFalse($iban->isValid('GB83WEST12345698765432'));
        self::assertFalse($iban->isValid('gb82west12345698765432'));
    }
}
