<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Calculator\LuhnCalculator;

/**
 * Calculator giving wrong check digit - replaced in container, card numbers and IMEI fail validation (negative tests)
 */
final class InvalidLuhnCalculator extends LuhnCalculator
{
    public function checkDigit(string $number): string
    {
        return (string) (((int) parent::checkDigit($number) + 1) % 10);
    }
}
