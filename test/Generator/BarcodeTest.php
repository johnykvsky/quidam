<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\BarcodeExtensionInterface;

final class BarcodeTest extends GeneratorTestCase
{
    public function testEan13(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $ean = $this->dummy->ext(BarcodeExtensionInterface::class)->ean13();
            self::assertMatchesRegularExpression('/^\d{13}$/', $ean);
            self::assertTrue(self::isGs1Valid($ean), $ean);
            // no restricted (02, 04, 2xx), coupon (05, 98x, 99x) and publication (977 - 979) prefixes
            self::assertDoesNotMatchRegularExpression('/^(0[245]|2|97[7-9]|9[89])/', $ean);
        }
    }

    public function testEan8(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $ean = $this->dummy->ext(BarcodeExtensionInterface::class)->ean8();
            self::assertMatchesRegularExpression('/^[3-9]\d{7}$/', $ean);
            self::assertTrue(self::isGs1Valid($ean), $ean);
        }
    }

    public function testUpcA(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $upc = $this->dummy->ext(BarcodeExtensionInterface::class)->upcA();
            self::assertMatchesRegularExpression('/^[01678]\d{11}$/', $upc);
            self::assertTrue(self::isGs1Valid($upc), $upc);
        }
    }

    public function testIsbn13AndIsmn(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $isbn = $this->dummy->ext(BarcodeExtensionInterface::class)->isbn13();
            self::assertMatchesRegularExpression('/^(978[0-9]|979(8|1[0-3]))\d+$/', $isbn);
            self::assertSame(13, strlen($isbn));
            self::assertTrue(self::isGs1Valid($isbn), $isbn);
            // 979-0 is ISMN, 978-6 groups end at 621
            self::assertDoesNotMatchRegularExpression('/^(9790|97862[2-9]|9786[3-9])/', $isbn);

            $ismn = $this->dummy->ext(BarcodeExtensionInterface::class)->ismn();
            self::assertMatchesRegularExpression('/^9790\d{9}$/', $ismn);
            self::assertTrue(self::isGs1Valid($ismn), $ismn);
        }
    }

    public function testIsbn10(): void
    {
        $withX = false;

        for ($i = 0; $i < 1000; ++$i) {
            $isbn = $this->dummy->ext(BarcodeExtensionInterface::class)->isbn10();
            self::assertMatchesRegularExpression('/^\d{9}[\dX]$/', $isbn);
            self::assertTrue(self::isIsbn10Valid($isbn), $isbn);
            // 6xx groups end at 621 (610 unassigned), there are no 7x / 6x two-digit groups
            self::assertDoesNotMatchRegularExpression('/^6(10|2[2-9]|[3-9])/', $isbn);
            $withX = $withX || str_ends_with($isbn, 'X');
        }

        self::assertTrue($withX, 'check digit X (10) is generated too');
    }

    private static function isGs1Valid(string $code): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($code)) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 === 1 ? 3 : 1);
        }

        return $sum % 10 === 0;
    }

    private static function isIsbn10Valid(string $isbn): bool
    {
        $sum = 0;
        foreach (str_split($isbn) as $position => $character) {
            $sum += (10 - $position) * ($character === 'X' ? 10 : (int) $character);
        }

        return $sum % 11 === 0;
    }
}
