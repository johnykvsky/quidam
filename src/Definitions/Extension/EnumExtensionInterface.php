<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

use BackedEnum;
use UnitEnum;

interface EnumExtensionInterface extends ExtensionInterface
{
    /**
     * @param class-string<T> $enum
     * @return T
     *
     * @template T of UnitEnum
     */
    public function enumCase(string $enum): UnitEnum;

    /** @param class-string<BackedEnum> $enum */
    public function enumValue(string $enum): int|string;
}
