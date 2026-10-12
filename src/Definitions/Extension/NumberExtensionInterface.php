<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface NumberExtensionInterface extends ExtensionInterface
{
    public function numberBetween(int $min = 0, int $max = PHP_INT_MAX): int;

    public function randomDigit(): int;

    public function randomDigitNotZero(): int;

    /**
     * @param int|null $nbDigits Number of digits, random (1-9) when null
     * @param bool $strict If true, result has exactly $nbDigits digits (no leading zeros)
     */
    public function randomNumber(?int $nbDigits = null, bool $strict = false): int;

    /**
     * Random float within [$min, $max], with at most $nbMaxDecimals decimals (trailing zeros are not kept).
     * Every value with given precision in range is equally likely.
     */
    public function randomFloat(int $nbMaxDecimals = 2, float $min = 0.0, float $max = 1000.0): float;

    /**
     * Normally distributed float (bell curve) - most values close to $mean, ~68% within one $stdDev, ~95% within two.
     * Realistic for heights, prices, durations, scores. Optional $min / $max cut the curve (values out of range are
     * drawn again, so the shape inside the range is kept), i.e. gaussian(170, 10, 140, 210, 1) → 174.3
     */
    public function gaussian(
        float $mean = 0.0,
        float $stdDev = 1.0,
        ?float $min = null,
        ?float $max = null,
        int $nbMaxDecimals = 2,
    ): float;

    /**
     * Normally distributed integer - like gaussian(), rounded to integer, i.e. age: gaussianInt(38, 12, 18, 90) → 41
     */
    public function gaussianInt(float $mean = 0.0, float $stdDev = 1.0, ?int $min = null, ?int $max = null): int;

    /**
     * Percentage within [$min, $max] (0-100 by default) with at most $nbMaxDecimals decimals, e.g. 42.0, 87.5
     */
    public function percentage(int $nbMaxDecimals = 0, float $min = 0.0, float $max = 100.0): float;

    /**
     * Mind the type: int is a percent, float is a probability - boolean(1) is 1% chance, boolean(1.0) is 100%
     *
     * @param int|float $chanceOfGettingTrue int as percent (0-100), float as probability (0.0-1.0)
     */
    public function boolean(int|float $chanceOfGettingTrue = 50): bool;
}
