<?php

declare(strict_types = 1);

namespace Quidam\Core;

use BackedEnum;
use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use UnitEnum;

class Enum implements EnumExtensionInterface
{
    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * @param class-string<T> $enum
     * @return T
     *
     * @template T of UnitEnum
     */
    public function enumCase(string $enum): UnitEnum
    {
        if (!enum_exists($enum)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a PHP enum', $enum));
        }

        $cases = $enum::cases();

        if ($cases === []) {
            throw new InvalidArgumentException(sprintf('Enum "%s" has no cases', $enum));
        }

        return $this->randomizer->randomElement($cases);
    }

    /** @param class-string<BackedEnum> $enum */
    public function enumValue(string $enum): int|string
    {
        if (!is_subclass_of($enum, BackedEnum::class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a backed PHP enum', $enum));
        }

        return $this->enumCase($enum)->value;
    }
}
