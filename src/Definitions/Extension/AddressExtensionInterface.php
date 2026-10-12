<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface AddressExtensionInterface extends ExtensionInterface
{
    /**
     * First part of some city names, e.g. "North", "Port"
     */
    public function cityPrefix(): string;

    /**
     * Ending of some city names, e.g. "ville", "ton"
     */
    public function citySuffix(): string;

    public function city(): string;

    public function streetName(): string;

    /**
     * e.g. "Avenue"
     */
    public function streetSuffix(): string;

    /**
     * Apartment, suite, unit or floor, e.g. "Apt. 482", "Suite 210"
     */
    public function secondaryAddress(): string;

    /**
     * State / province / region name, e.g. "Central Region" (real ones in language packs)
     */
    public function state(): string;

    /**
     * State / province / region code, e.g. "CR"
     */
    public function stateCode(): string;

    /**
     * Matching code and name of one state / region, e.g. ['code' => 'CR', 'name' => 'Central Region']
     *
     * @return array{code: string, name: string}
     */
    public function stateData(): array;

    public function buildingNumber(): string;

    /**
     * Street and building number (STREET_ADDRESS_FORMAT), e.g. "Fisher Street 12"
     */
    public function streetAddress(): string;

    public function postcode(): string;

    /**
     * Address line (ADDRESS_FORMAT), e.g. "Fisher Street 12, 12345 Lakeville"
     */
    public function address(): string;

    /**
     * County / district, e.g. "Harrison District" - fictional in default data
     */
    public function county(): string;

    /**
     * City with its region, county, postcode and coordinates, e.g. ['city' => 'North Johnhaven', 'state' => 'Central Region',
     * 'stateCode' => 'CR', 'county' => 'Harrison District', 'postcode' => '75201', 'latitude' => ..., 'longitude' => ...].
     * Default data is fictional - region and code match, coordinates are random; language packs return real places
     * where all values match.
     *
     * @param string|null $stateCode only this region (a code of STATES)
     * @return array{city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float}
     */
    public function cityData(?string $stateCode = null): array;

    /**
     * cityData() with a street and the formatted address line built from these parts
     *
     * @param string|null $stateCode only this region (a code of STATES)
     * @return array{street: string, city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float, address: string}
     */
    public function addressData(?string $stateCode = null): array;

    public function country(): string;

    /**
     * ISO 3166-1 alpha-2 country code, e.g. "PL"
     */
    public function countryCode(): string;

    /**
     * ISO 3166-1 alpha-3 country code, e.g. "POL"
     */
    public function countryCodeAlpha3(): string;

    /**
     * Matching codes and name of one country, e.g. ['alpha2' => 'PL', 'alpha3' => 'POL', 'name' => 'Poland']
     *
     * @return array{alpha2: string, alpha3: string, name: string}
     */
    public function countryData(): array;

    /**
     * Latitude with points spread evenly over Earth surface (not over degrees, which would crowd them at poles)
     */
    public function latitude(float $min = -90.0, float $max = 90.0): float;

    public function longitude(float $min = -180.0, float $max = 180.0): float;

    /** @return array{latitude: float, longitude: float} */
    public function coordinates(): array;
}
