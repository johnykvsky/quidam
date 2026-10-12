<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Test\Fixtures\EmptyEnum;
use Quidam\Test\Fixtures\Suit;
use Quidam\Test\Fixtures\UnitSuit;

final class EnumTest extends GeneratorTestCase
{
    public function testEnum(): void
    {
        $enum = $this->dummy->ext(EnumExtensionInterface::class);

        self::assertInstanceOf(Suit::class, $enum->enumCase(Suit::class));
        self::assertInstanceOf(UnitSuit::class, $enum->enumCase(UnitSuit::class));
        $this->assertAlwaysMatches('/^(H|S)$/', static fn (): string|int => $enum->enumValue(Suit::class));
    }

    public function testEmptyEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(EnumExtensionInterface::class)->enumCase(EmptyEnum::class);
    }

    public function testNotBackedEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        $this->dummy->ext(EnumExtensionInterface::class)->enumValue(UnitSuit::class);
    }

    public function testNotEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type, argument.templateType
        $this->dummy->ext(EnumExtensionInterface::class)->enumCase(\stdClass::class);
    }
}
