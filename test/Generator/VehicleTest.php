<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Core\Vehicle;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use ReflectionMethod;

final class VehicleTest extends GeneratorTestCase
{
    public function testVinHasValidStructureAndCheckDigit(): void
    {
        $vehicle = $this->dummy->ext(VehicleExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT * 4; ++$i) {
            $vin = $vehicle->vin();
            self::assertMatchesRegularExpression('/^[A-HJ-NPR-Z0-9]{17}$/', $vin);
            self::assertSame(self::checkDigit($vin), $vin[8], $vin);
        }
    }

    public function testCheckDigitOfKnownVins(): void
    {
        $method = new ReflectionMethod(Vehicle::class, 'vinCheckDigit');

        self::assertSame('X', $method->invoke($this->dummy->ext(VehicleExtensionInterface::class), '1M8GDM9AXKP042788'));
        self::assertSame('3', $method->invoke($this->dummy->ext(VehicleExtensionInterface::class), '1HGCM82633A004352'));
    }

    public function testVinOfManufacturer(): void
    {
        $this->assertAlwaysMatches('/^(5YJ|7SA)/', fn (): string => $this->dummy->ext(VehicleExtensionInterface::class)->vin('Tesla'));
    }

    public function testVehicleModelsMatchManufacturer(): void
    {
        $vehicle = $this->dummy->ext(VehicleExtensionInterface::class);

        $this->assertAlwaysMatches('/^(Model [3YSX])$/', static fn (): string => $vehicle->model('Tesla'));
        $this->assertAlwaysMatches('/^[A-Z][\w-]+ \S/', $vehicle->vehicle(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z -]+$/', $vehicle->manufacturer(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+$/', $vehicle->type(...));
        $this->assertAlwaysMatches('/^(Gasoline|Diesel|Hybrid|Electric|Plug-in Hybrid|LPG)$/', $vehicle->fuel(...));
        $this->assertAlwaysMatches('/^[A-Z0-9 -]{7,9}$/', $vehicle->licensePlate(...));
    }

    public function testUnknownManufacturer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('use one of: Audi');
        $this->dummy->ext(VehicleExtensionInterface::class)->vin('Trabant');
    }

    /**
     * Independent implementation of ISO 3779 / 49 CFR 565 check digit
     */
    private static function checkDigit(string $vin): string
    {
        $values = array_combine(str_split('ABCDEFGHJKLMNPRSTUVWXYZ'), [1, 2, 3, 4, 5, 6, 7, 8, 1, 2, 3, 4, 5, 7, 9, 2, 3, 4, 5, 6, 7, 8, 9]);
        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        foreach (str_split($vin) as $position => $character) {
            $sum += (is_numeric($character) ? (int) $character : $values[$character]) * $weights[$position];
        }

        return $sum % 11 === 10 ? 'X' : (string) ($sum % 11);
    }
}
