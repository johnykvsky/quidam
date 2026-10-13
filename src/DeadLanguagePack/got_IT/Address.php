<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\got_IT;

use Quidam\Core\Address as CoreAddress;
use Quidam\Definitions\Enum\Gender;
use Quidam\Exception\InvalidArgumentException;

/**
 * Addresses of the Gothic kingdoms: "Gatwo Fiskjane ·𐌹𐌱·, Ravenna" - towns from the Black Sea to Toledo under their
 * late Roman names, with the coordinates of the site today; house numbers in Gothic numerals, districts (gawi) named
 * after their lords. Postcodes are Italian and not in address lines.
 */
class Address extends CoreAddress
{
    use GothicAlphabetTrait;

    /** @var list<string> */
    protected const array CITY_PREFIX = ['Mikila', 'Niuja'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['gards', 'haims', 'baurgs'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['gatwo', 'plapja'];

    /**
     * Streets (gatwo) and squares (plapja) - of the king, the bishop, fishers, smiths, grain, wine and bread
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Gatwo Þiudanis', 'Gatwo Aipiskaupaus', 'Gatwo Fiskjane', 'Gatwo Smiþane', 'Gatwo Kaurnis', 'Gatwo Weinis',
        'Gatwo Hlaibis', 'Plapja',
    ];

    /** @var list<string> masks for strings()->numerify() - Italian postcodes */
    protected const array POSTCODE = ['#####'];

    /** @var list<string> - house, tower, roof */
    protected const array SECONDARY_ADDRESS = ['razn', 'kelikn', 'hrot'];

    /**
     * Lands of the Gothic kingdoms - in Gothic where Wulfila's Bible or calendar names them ("Spania", "Gutþiuda")
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'IT' => 'Italia', 'GA' => 'Gallia', 'SP' => 'Spania', 'MO' => 'Moesia', 'GU' => 'Gutþiuda',
    ];

    /** No postcode - "Gatwo Fiskjane ·𐌹𐌱·, Ravenna" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [late Roman name, land code, latitude, longitude] - coordinates of the site today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Ravenna', 'IT', 44.418, 12.204], ['Verona', 'IT', 45.438, 10.992], ['Ticinum', 'IT', 45.185, 9.158],
        ['Roma', 'IT', 41.893, 12.483], ['Mediolanum', 'IT', 45.464, 9.190], ['Neapolis', 'IT', 40.852, 14.268],
        ['Tolosa', 'GA', 43.605, 1.444], ['Burdigala', 'GA', 44.838, -0.579], ['Narbo', 'GA', 43.184, 3.004],
        ['Carcaso', 'GA', 43.212, 2.353],
        ['Toletum', 'SP', 39.857, -4.024], ['Barcino', 'SP', 41.383, 2.177], ['Emerita Augusta', 'SP', 38.916, -6.344],
        ['Hispalis', 'SP', 37.389, -5.984], ['Reccopolis', 'SP', 40.348, -2.874],
        ['Nicopolis ad Istrum', 'MO', 43.218, 25.611], ['Durostorum', 'MO', 44.117, 27.267], ['Novae', 'MO', 43.616, 25.383],
        ['Doros', 'GU', 44.593, 33.808], ['Chersonesus', 'GU', 44.611, 33.493],
    ];

    /**
     * A real town of the Gothic kingdoms, e.g. "Toletum" - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Gatwo Fiskjane" (fishers' street), "Plapja" (square)
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * House number in Gothic numerals, e.g. "·𐌹𐌱·" (12)
     */
    public function buildingNumber(): string
    {
        return $this->gothicNumeral($this->randomizer->getInt(1, 60));
    }

    /**
     * Gothic numeral of 1-999 between dots, e.g. numeral(12) -> "·𐌹𐌱·", numeral(444) -> "·𐍅𐌼𐌳·"; other numbers
     * as digits
     */
    public function numeral(int $number): string
    {
        return $this->gothicNumeral($number);
    }

    /**
     * District (gawi) of its lord, name in genitive, e.g. "gawi Ataulfis"
     */
    public function county(): string
    {
        $name = $this->person->firstName(Gender::Male);

        return 'gawi ' . ($this->person instanceof Person ? $this->person->genitive($name) : $name);
    }

    /**
     * A real town with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Toletum',
     * 'state' => 'Spania', 'stateCode' => 'SP', 'county' => 'gawi Ataulfis', 'postcode' => '48121', ...]
     *
     * @param string|null $stateCode only this land (a code of STATES)
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

        [$city, $code, $latitude, $longitude] = $this->randomizer->randomElement($places);

        return [
            'city' => $city,
            'state' => static::STATES[$code],
            'stateCode' => $code,
            'county' => $this->county(),
            'postcode' => $this->postcode(),
            'latitude' => round($latitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
            'longitude' => round($longitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
        ];
    }
}
