<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\pl_PL;

use PHPUnit\Framework\TestCase;
use Quidam\Exception\InvalidArgumentException;
use Quidam\LanguagePack\pl_PL\Address;
use Quidam\LanguagePack\pl_PL\PlPlGenerator;
use ReflectionClassConstant;

/**
 * Real places: city, voivodeship, county, postcode and coordinates of an address belong to one place
 */
final class PlacesTest extends TestCase
{
    private const int REPEAT = 300;

    public function testAddressPartsMatchOnePlace(): void
    {
        $address = PlPlGenerator::create()->address();
        $places = $this->places();

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $data = $address->addressData();
            $place = $places[$data['city'] . '|' . $data['stateCode']] ?? null;
            self::assertNotNull($place, 'real place: ' . $data['address']);
            self::assertSame($place['county'], $data['county']);
            self::assertArrayHasKey($data['postcode'], $place['postcodes'], 'postcode of the city: ' . $data['address']);
            [$latitude, $longitude] = $place['postcodes'][$data['postcode']];
            self::assertEqualsWithDelta($latitude, $data['latitude'], 0.0031);
            self::assertEqualsWithDelta($longitude, $data['longitude'], 0.0031);
            self::assertMatchesRegularExpression('/^\d{2}-\d{3}$/', $data['postcode']);
            self::assertSame($data['street'] . ', ' . $data['postcode'] . ' ' . $data['city'], $data['address']);
        }
    }

    public function testPlacesData(): void
    {
        $raw = $this->rawPlaces();
        $states = new ReflectionClassConstant(Address::class, 'STATES')->getValue();
        self::assertIsArray($states);
        self::assertCount(16, $states, 'voivodeships');
        self::assertCount(count($raw), $this->places(), 'city and voivodeship unique');

        foreach ($raw as $place) {
            self::assertArrayHasKey($place[1], $states, $place[0]);
            self::assertNotEmpty($place[3], $place[0]);

            foreach ($place[3] as [$postcode, $latitude, $longitude]) {
                self::assertMatchesRegularExpression('/^\d{2}-\d{3}$/', $postcode);
                // bounding box of Poland
                self::assertTrue($latitude >= 49.0 && $latitude <= 54.9 && $longitude >= 14.1 && $longitude <= 24.2, $place[0] . ' ' . $postcode);
            }
        }
    }

    public function testRegionFilter(): void
    {
        $address = PlPlGenerator::create()->address();

        for ($i = 0; $i < 50; ++$i) {
            self::assertSame('PL-14', $address->addressData('pl-14')['stateCode']);
            self::assertSame('mazowieckie', $address->cityData('PL-14')['state']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX-99');
    }

    /** @return array<string, array{county: string, postcodes: array<string, array{float, float}>}> */
    private function places(): array
    {
        $places = [];

        foreach ($this->rawPlaces() as [$city, $state, $county, $postcodes]) {
            $points = [];
            foreach ($postcodes as [$postcode, $latitude, $longitude]) {
                $points[$postcode] = [$latitude, $longitude];
            }

            $places[$city . '|' . $state] = ['county' => $county, 'postcodes' => $points];
        }

        return $places;
    }

    /** @return list<array{string, string, string, list<array{string, float, float}>}> */
    private function rawPlaces(): array
    {
        $raw = new ReflectionClassConstant(Address::class, 'PLACES')->getValue();
        self::assertIsArray($raw);

        return $raw; // @phpstan-ignore return.type
    }
}
