<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use Quidam\Core\Address as CoreAddress;
use Quidam\Definitions\Enum\Gender;
use Quidam\Exception\InvalidArgumentException;

/**
 * Addresses in the Empire: "Path of Honor cha'maH wej, First City" - building numbers in Klingon (23), cities on the
 * worlds of the Empire, Great Houses as counties. Coordinates are on the city's world, so they're random; postcodes
 * are not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Fort', 'New', 'Old', 'High', 'Low'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['ath', 'okh', 'esh', 'ar', 'tal'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['Path', 'Road', 'Street', 'Way', 'Alley'];

    /** @var list<string> */
    protected const array STREETS = [
        'Path of Honor', 'Road of Blood', 'Street of the Fallen', "Warriors' Way", 'Avenue of Victory',
        'Lane of the Targ', 'Bloodwine Alley', 'Forge Street', 'Road of Champions', 'Glory Path', 'Iron Gate Street',
        'Path of the Ancestors',
    ];

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['Quarters', 'Deck', 'Barracks'];

    /**
     * Worlds of the Empire - the homeworld and a few known ones, the rest made up
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'QO' => "Qo'noS", 'BO' => 'Boreth', 'KH' => 'Khitomer', 'RP' => 'Rura Penthe', 'TG' => "Ty'Gokor",
        'MP' => 'Morath Prime', 'GV' => 'Ghorvak IV', 'KT' => 'Kethrin', 'QT' => "Qal'tor", 'VK' => "Vor'kesh",
    ];

    /**
     * Cities as [name, world code]
     *
     * @var list<array{string, string}>
     */
    protected const array PLACES = [
        ['First City', 'QO'], ["Tar'Kesh", 'QO'], ['Ghorvath', 'QO'], ["Mok'tal", 'QO'], ['Qeldar', 'QO'],
        ['Krennok', 'QO'], ['Ghurath', 'BO'], ["Vel'tok", 'BO'], ['Tagrath', 'KH'], ['Khitomer Outpost', 'KH'],
        ['Dilithium Mines', 'RP'], ['Hall of Warriors', 'TG'], ["Ty'Gokor Citadel", 'TG'], ['Morath', 'MP'],
        ["Kor'vesh", 'MP'], ['Ghorvak Station', 'GV'], ['Drakkar', 'GV'], ['Kethrin Landing', 'KT'],
        ["Qal'tor Keep", 'QT'], ['Varnak', 'VK'], ["Vor'kesh Spires", 'VK'],
    ];

    /** No postcode - "Path of Honor cha'maH wej, First City" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /** Digits 1-9 - 10 is wa'maH (one ten), 100 wa'vatlh (one hundred) */
    private const array DIGITS = ['', "wa'", "cha'", 'wej', 'loS', 'vagh', 'jav', 'Soch', 'chorgh', 'Hut'];

    /**
     * e.g. "First City"
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Path of Honor"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * Number up to 199 in Klingon, small ones more often, e.g. "cha'maH wej" (23)
     */
    public function buildingNumber(): string
    {
        return $this->klingonNumber($this->randomizer->getInt(1, $this->randomizer->randomElement([20, 50, 199])));
    }

    /**
     * e.g. "Deck vagh"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS) . ' ' . $this->klingonNumber($this->randomizer->getInt(1, 12));
    }

    /**
     * Great House, e.g. "House of Torvek"
     */
    public function county(): string
    {
        return 'House of ' . $this->person->firstName(Gender::Male);
    }

    /**
     * A city with its world, e.g. ['city' => 'First City', 'state' => "Qo'noS", 'stateCode' => 'QO',
     * 'county' => 'House of Torvek', ...] - coordinates on that world, random
     *
     * @param string|null $stateCode only this world (a code of STATES)
     */
    public function cityData(?string $stateCode = null): array
    {
        $places = static::PLACES;

        if ($stateCode !== null) {
            $places = array_values(array_filter($places, static fn (array $place): bool => strcasecmp($place[1], $stateCode) === 0));

            if ($places === []) {
                throw new InvalidArgumentException(sprintf('Unknown state code "%s"', $stateCode));
            }
        }

        [$city, $code] = $this->randomizer->randomElement($places);

        return [
            'city' => $city,
            'state' => static::STATES[$code],
            'stateCode' => $code,
            'county' => $this->county(),
            'postcode' => $this->postcode(),
        ] + $this->coordinates();
    }

    /**
     * 1-999 in Klingon - hundreds (vatlh), tens (maH), units: 123 -> "wa'vatlh cha'maH wej"
     */
    private function klingonNumber(int $number): string
    {
        $parts = [];

        foreach ([100 => 'vatlh', 10 => 'maH'] as $value => $word) {
            if ($number >= $value) {
                $parts[] = self::DIGITS[intdiv($number, $value)] . $word;
                $number %= $value;
            }
        }

        if ($number > 0) {
            $parts[] = self::DIGITS[$number];
        }

        return implode(' ', $parts);
    }
}
