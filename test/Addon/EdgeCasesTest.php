<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\CustomEdgeCases;

final class EdgeCasesTest extends TestCase
{
    private EdgeCasesInterface $edgeCases;

    protected function setUp(): void
    {
        $this->edgeCases = Quidam::create()->ext(EdgeCasesInterface::class);
    }

    public function testCategories(): void
    {
        self::assertSame(
            ['blank', 'reserved', 'numeric', 'unicode', 'rtl', 'invisible', 'emoji', 'injection'],
            $this->edgeCases->categories(),
        );
    }

    public function testStringsAreValidUtf8(): void
    {
        $all = $this->edgeCases->strings();

        self::assertGreaterThan(80, count($all));
        foreach ($all as $string) {
            self::assertTrue(mb_check_encoding($string, 'UTF-8'), bin2hex($string));
        }
    }

    public function testStringFromCategory(): void
    {
        $emoji = $this->edgeCases->strings('emoji');

        for ($i = 0; $i < 50; ++$i) {
            self::assertContains($this->edgeCases->string('emoji'), $emoji);
            self::assertContains($this->edgeCases->string(), $this->edgeCases->strings());
        }

        self::assertContains('', $this->edgeCases->strings('blank'));
        self::assertContains('<script>alert(1)</script>', $this->edgeCases->strings('injection'));
    }

    public function testUnknownCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Available: blank, reserved');
        $this->edgeCases->string('nope');
    }

    public function testLongString(): void
    {
        $ascii = $this->edgeCases->longString(300);
        self::assertSame(300, strlen($ascii));
        self::assertMatchesRegularExpression('/^[a-zA-Z]+$/', $ascii);

        $multibyte = $this->edgeCases->longString(300, true);
        self::assertSame(300, mb_strlen($multibyte));
        self::assertGreaterThan(600, strlen($multibyte));
        self::assertTrue(mb_check_encoding($multibyte, 'UTF-8'));

        $this->expectException(InvalidArgumentException::class);
        $this->edgeCases->longString(0);
    }

    public function testSubclassAddsCategory(): void
    {
        $custom = Quidam::create()->ext(CustomEdgeCases::class);

        self::assertContains('sku', $custom->categories());
        self::assertSame('SKU-', $custom->string('sku'));
    }
}
