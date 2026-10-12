<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class AddressTest extends GeneratorTestCase
{
    public function testAddressParts(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        $this->assertAlwaysMatches('/^[1-9]\d{0,2}$/', $address->buildingNumber(...));
        $this->assertAlwaysMatches('/^\d{4,5}$/', $address->postcode(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+ [A-Z][a-z]+$/', $address->streetName(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+ [A-Z][a-z]+ [1-9]\d{0,2}$/', $address->streetAddress(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+( [A-Z][A-Za-z]+)?$/', $address->city(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+ [A-Z][a-z]+ [1-9]\d{0,2}, \d{4,5} [A-Z][A-Za-z ]+$/', $address->address(...));
    }

    public function testCountry(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        $this->assertAlwaysMatches('/^\S.+$/u', $address->country(...));
        $this->assertAlwaysMatches('/^[A-Z]{2}$/', $address->countryCode(...));
        $this->assertAlwaysMatches('/^[A-Z]{3}$/', $address->countryCodeAlpha3(...));

        $names = [];
        for ($i = 0; $i < 1000; ++$i) {
            $country = $address->countryData();
            self::assertMatchesRegularExpression('/^[A-Z]{2}$/', $country['alpha2']);
            self::assertMatchesRegularExpression('/^[A-Z]{3}$/', $country['alpha3']);
            self::assertSame($country, $names[$country['alpha2']] ?? $country, 'same code always has same data');
            $names[$country['alpha2']] = $country;
        }

        self::assertSame(['alpha2' => 'PL', 'alpha3' => 'POL', 'name' => 'Poland'], $names['PL'] ?? ['alpha2' => 'PL', 'alpha3' => 'POL', 'name' => 'Poland']);
    }

    public function testCoordinates(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $coordinates = $address->coordinates();
            self::assertGreaterThanOrEqual(-90, $coordinates['latitude']);
            self::assertLessThanOrEqual(90, $coordinates['latitude']);
            self::assertGreaterThanOrEqual(-180, $coordinates['longitude']);
            self::assertLessThanOrEqual(180, $coordinates['longitude']);

            $latitude = $address->latitude(50.0, 51.0);
            self::assertGreaterThanOrEqual(50.0, $latitude);
            self::assertLessThanOrEqual(51.0, $latitude);
        }
    }

    public function testLatitudeIsSpreadOverSurface(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);
        $polar = 0;

        for ($i = 0; $i < 10000; ++$i) {
            if (abs($address->latitude()) > 66.56) {
                ++$polar;
            }
        }

        // area beyond polar circles is ~8.3% of Earth surface (uniform degrees would give ~26%)
        self::assertGreaterThan(600, $polar);
        self::assertLessThan(1100, $polar);
    }

    public function testCoordinatesStayInNarrowRange(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $latitude = $address->latitude(50.0000001, 50.0000004);
            self::assertGreaterThanOrEqual(50.0000001, $latitude);
            self::assertLessThanOrEqual(50.0000004, $latitude);
            self::assertSame(90.0, $address->latitude(90.0, 90.0));
        }
    }

    public function testInvalidCoordinatesRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(AddressExtensionInterface::class)->longitude(-200.0);
    }

    public function testStateSecondaryAddressAndStreetSuffix(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $state = $address->stateData();
            self::assertMatchesRegularExpression('/^[A-Z]{2}$/', $state['code']);
            self::assertMatchesRegularExpression('/^[A-Z][A-Za-z ]+$/', $state['name']);
        }

        $this->assertAlwaysMatches('/^[A-Z]{2}$/', $address->stateCode(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z ]+$/', $address->state(...));
        $this->assertAlwaysMatches('/^(Apt\.|Suite|Unit|Floor) [1-9]\d*$/', $address->secondaryAddress(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $address->streetSuffix(...));
    }

    public function testCityParts(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $address->cityPrefix(...));
        $this->assertAlwaysMatches('/^[a-z]+$/', $address->citySuffix(...));
    }

    public function testCityAndAddressData(): void
    {
        $address = $this->dummy->ext(AddressExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $data = $address->addressData();

            self::assertSame(['street', 'city', 'state', 'stateCode', 'county', 'postcode', 'latitude', 'longitude', 'address'], array_keys($data));
            self::assertSame($data['street'] . ', ' . $data['postcode'] . ' ' . $data['city'], $data['address']);
            self::assertMatchesRegularExpression('/^[A-Z][A-Za-z\'-]+ District$/', $data['county']);
            self::assertSame('CR', $address->cityData('cr')['stateCode']);
            self::assertSame('Central Region', $address->addressData('CR')['state']);
        }

        $this->expectException(InvalidArgumentException::class);
        $address->cityData('XX');
    }
}
