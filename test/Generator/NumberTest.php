<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;
use Quidam\Quidam;

final class NumberTest extends GeneratorTestCase
{
    public function testNumbers(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        $this->assertAlwaysMatches('/^(5|6|7)$/', static fn (): int => $number->numberBetween(5, 7));
        $this->assertAlwaysMatches('/^\d$/', $number->randomDigit(...));
        $this->assertAlwaysMatches('/^[1-9]$/', $number->randomDigitNotZero(...));
        $this->assertAlwaysMatches('/^\d{1,3}$/', static fn (): int => $number->randomNumber(3));
        $this->assertAlwaysMatches('/^[1-9]\d{2}$/', static fn (): int => $number->randomNumber(3, true));
        $this->assertAlwaysMatches('/^\d+$/', static fn (): int => $number->randomNumber());
    }

    public function testRandomFloat(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $float = $number->randomFloat(2, 1.0, 2.0);
            self::assertGreaterThanOrEqual(1.0, $float);
            self::assertLessThanOrEqual(2.0, $float);
            self::assertSame(round($float, 2), $float);
        }
    }

    public function testRandomFloatStaysInRangeAfterRounding(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        for ($i = 0; $i < 200; ++$i) {
            $float = $number->randomFloat(1, 0.61, 0.79);
            self::assertContains($float, [0.7]);

            $float = $number->randomFloat(0, 0.6, 2.4);
            self::assertContains($float, [1.0, 2.0]);
        }

        self::assertSame(1.1, $number->randomFloat(2, 1.1, 1.1));
        self::assertSame(-0.5, $number->randomFloat(1, -0.5, -0.5));
    }

    public function testRandomFloatWithoutValueInRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no number with 0 decimals');
        $this->dummy->ext(NumberExtensionInterface::class)->randomFloat(0, 0.6, 0.9);
    }

    public function testRandomFloatDefaults(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $float = $number->randomFloat();
            self::assertGreaterThanOrEqual(0.0, $float);
            self::assertLessThanOrEqual(1000.0, $float);
            self::assertSame(round($float, 2), $float);
        }
    }

    public function testBoolean(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        self::assertTrue($number->boolean(100));
        self::assertFalse($number->boolean(0));
    }

    public function testInvalidRandomNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(NumberExtensionInterface::class)->randomNumber(19);
    }

    public function testInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(NumberExtensionInterface::class)->numberBetween(2, 1);
    }

    public function testGaussian(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);
        $values = [];

        for ($i = 0; $i < 2000; ++$i) {
            $values[] = $number->gaussian(100.0, 15.0);
        }

        $mean = array_sum($values) / count($values);
        $stdDev = sqrt(array_sum(array_map(static fn (float $v): float => ($v - $mean) ** 2, $values)) / count($values));
        $withinOne = count(array_filter($values, static fn (float $v): bool => abs($v - 100.0) <= 15.0)) / count($values);

        self::assertEqualsWithDelta(100.0, $mean, 2.0);
        self::assertEqualsWithDelta(15.0, $stdDev, 2.0);
        self::assertEqualsWithDelta(0.68, $withinOne, 0.05);
    }

    public function testGaussianRangeAndDecimals(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        for ($i = 0; $i < 500; ++$i) {
            $float = $number->gaussian(170.0, 10.0, 160.0, 175.0, 1);
            self::assertGreaterThanOrEqual(160.0, $float);
            self::assertLessThanOrEqual(175.0, $float);
            self::assertSame(round($float, 1), $float);

            $int = $number->gaussianInt(38.0, 12.0, 18, 90);
            self::assertGreaterThanOrEqual(18, $int);
            self::assertLessThanOrEqual(90, $int);
        }

        self::assertSame(5.0, $number->gaussian(5.0, 0.0));
        self::assertSame(5, $number->gaussianInt(5.4, 0.0));
    }

    public function testGaussianSeedIsRepeatable(): void
    {
        self::assertSame(
            Quidam::create(seed: 7)->ext(NumberExtensionInterface::class)->gaussian(10.0, 2.0),
            Quidam::create(seed: 7)->ext(NumberExtensionInterface::class)->gaussian(10.0, 2.0),
        );
    }

    public function testGaussianRangeTooFarFromMean(): void
    {
        $this->expectException(OverflowException::class);
        $this->dummy->ext(NumberExtensionInterface::class)->gaussian(0.0, 1.0, 50.0);
    }

    public function testGaussianInvalidArguments(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        foreach (
            [
            static fn (): float => $number->gaussian(0.0, -1.0),
            static fn (): float => $number->gaussian(0.0, 1.0, 2.0, 1.0),
            static fn (): float => $number->gaussian(0.0, 1.0, null, null, -1),
            static fn (): int => $number->gaussianInt(0.0, 1.0, 5, 1),
            ] as $call
        ) {
            try {
                $call();
                self::fail('InvalidArgumentException expected');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testPercentage(): void
    {
        $number = $this->dummy->ext(NumberExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $percentage = $number->percentage();
            self::assertGreaterThanOrEqual(0.0, $percentage);
            self::assertLessThanOrEqual(100.0, $percentage);
            self::assertSame(round($percentage), $percentage);

            $precise = $number->percentage(2, 10.0, 20.0);
            self::assertGreaterThanOrEqual(10.0, $precise);
            self::assertLessThanOrEqual(20.0, $precise);
        }

        $this->expectException(InvalidArgumentException::class);
        $number->percentage(0, 0.0, 120.0);
    }
}
