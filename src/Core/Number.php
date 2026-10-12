<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;

class Number implements NumberExtensionInterface
{
    /** Integers up to 2^53 are represented exactly as float */
    private const int MAX_EXACT_FLOAT_INT = 9007199254740992;

    /** Samples drawn by gaussian() before giving up on a range too far from the mean */
    private const int MAX_GAUSSIAN_ATTEMPTS = 1000;

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    public function numberBetween(int $min = 0, int $max = PHP_INT_MAX): int
    {
        if ($min > $max) {
            throw new InvalidArgumentException('$min cannot be higher than $max');
        }

        return $this->randomizer->getInt($min, $max);
    }

    public function randomDigit(): int
    {
        return $this->randomizer->getInt(0, 9);
    }

    public function randomDigitNotZero(): int
    {
        return $this->randomizer->getInt(1, 9);
    }

    /**
     * @param int|null $nbDigits Number of digits, random (1-9) when null
     * @param bool $strict If true, result has exactly $nbDigits digits (no leading zeros)
     */
    public function randomNumber(?int $nbDigits = null, bool $strict = false): int
    {
        $nbDigits ??= $this->randomDigitNotZero();

        if ($nbDigits < 1 || $nbDigits > 18) {
            throw new InvalidArgumentException('randomNumber() $nbDigits must be between 1 and 18');
        }

        $max = 10 ** $nbDigits - 1;

        return $this->randomizer->getInt($strict ? 10 ** ($nbDigits - 1) : 0, $max);
    }

    /**
     * Random float within [$min, $max], with at most $nbMaxDecimals decimals (trailing zeros are not kept).
     * Every value with given precision in range is equally likely.
     */
    public function randomFloat(int $nbMaxDecimals = 2, float $min = 0.0, float $max = 1000.0): float
    {
        if ($nbMaxDecimals < 0) {
            throw new InvalidArgumentException('$nbMaxDecimals cannot be negative');
        }

        if ($min > $max) {
            throw new InvalidArgumentException('$min cannot be higher than $max');
        }

        $factor = 10.0 ** $nbMaxDecimals;
        // rounding before ceil/floor removes float noise, i.e. 1.1 * 100 = 110.00000000000001
        $low = ceil(round($min * $factor, 6));
        $high = floor(round($max * $factor, 6));

        if ($low > $high) {
            throw new InvalidArgumentException(sprintf('There is no number with %d decimals between %F and %F', $nbMaxDecimals, $min, $max));
        }

        // pick integer from scaled range, so result is never rounded out of [$min, $max]
        if (abs($low) <= self::MAX_EXACT_FLOAT_INT && abs($high) <= self::MAX_EXACT_FLOAT_INT) {
            return round($this->randomizer->getInt((int) $low, (int) $high) / $factor, $nbMaxDecimals);
        }

        // range too big for integer scaling - round and keep within limits
        return min(max(round($this->randomizer->getFloat($min, $max), $nbMaxDecimals), $min), $max);
    }

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
    ): float {
        if ($nbMaxDecimals < 0) {
            throw new InvalidArgumentException('$nbMaxDecimals cannot be negative');
        }

        return $this->sampleGaussian($mean, $stdDev, $min, $max, static fn (float $value): float => round($value, $nbMaxDecimals));
    }

    /**
     * Normally distributed integer - like gaussian(), rounded to integer, i.e. age: gaussianInt(38, 12, 18, 90) → 41
     */
    public function gaussianInt(float $mean = 0.0, float $stdDev = 1.0, ?int $min = null, ?int $max = null): int
    {
        return (int) $this->sampleGaussian($mean, $stdDev, $min, $max, static fn (float $value): float => round($value));
    }

    /**
     * Percentage within [$min, $max] (0-100 by default) with at most $nbMaxDecimals decimals, e.g. 42.0, 87.5
     */
    public function percentage(int $nbMaxDecimals = 0, float $min = 0.0, float $max = 100.0): float
    {
        if ($min < 0.0 || $max > 100.0) {
            throw new InvalidArgumentException('percentage() range must be within 0-100');
        }

        return $this->randomFloat($nbMaxDecimals, $min, $max);
    }

    /**
     * Mind the type: int is a percent, float is a probability - boolean(1) is 1% chance, boolean(1.0) is 100%
     *
     * @param int|float $chanceOfGettingTrue int as percent (0-100), float as probability (0.0-1.0)
     */
    public function boolean(int|float $chanceOfGettingTrue = 50): bool
    {
        return $this->randomizer->getBool($chanceOfGettingTrue);
    }

    /**
     * Box-Muller transform, out of range values are drawn again (truncated normal distribution)
     *
     * @param callable(float): float $round
     */
    protected function sampleGaussian(float $mean, float $stdDev, int|float|null $min, int|float|null $max, callable $round): float
    {
        if ($stdDev < 0) {
            throw new InvalidArgumentException('$stdDev cannot be negative');
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException('$min cannot be higher than $max');
        }

        for ($attempt = 0; $attempt < self::MAX_GAUSSIAN_ATTEMPTS; ++$attempt) {
            do {
                $u1 = $this->randomizer->getFloat(0.0, 1.0);
            } while ($u1 <= 0.0);

            $u2 = $this->randomizer->getFloat(0.0, 1.0);
            $value = $round($mean + $stdDev * sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2));

            if (($min === null || $value >= $min) && ($max === null || $value <= $max)) {
                return $value;
            }
        }

        throw new OverflowException(sprintf(
            'No value within [%s, %s] found in %d attempts - range is too far from mean %s (standard deviation %s)',
            $min ?? '-INF',
            $max ?? 'INF',
            self::MAX_GAUSSIAN_ATTEMPTS,
            $mean,
            $stdDev,
        ));
    }
}
