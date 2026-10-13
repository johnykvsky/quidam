<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\gmh_DE;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Medieval addresses: "Kornmarket, hûs zem Swanen, Wormez" - town houses had names, not numbers. Towns of the Empire
 * in Middle High German spelling, from Santen on the Rhine to Wiene on the Danube (the Nibelungs' road), with the
 * coordinates of the town today; dioceses as counties. Postcodes are German and not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Nider', 'Ober', 'Niuwen', 'Alten'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['burc', 'berc', 'dorf', 'heim', 'stat', 'vurt'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['gazze', 'strâze', 'market'];

    /** @var list<string> */
    protected const array STREETS = [
        'Market', 'Kornmarket', 'Vischmarket', 'Smidegazze', 'Brôtgazze', 'Kirchgazze', 'Burcgazze', 'Hôhe strâze',
        'Rîngazze', 'Mülngazze', 'Wîngazze',
    ];

    /**
     * House names - "zem" (to the) for masculine and neuter, "zer" for feminine
     *
     * @var list<string>
     */
    protected const array HOUSES = [
        'zem Swanen', 'zem Hirze', 'zem Lewen', 'zem Bern', 'zem Adelar', 'zem Einhürne', 'zem Sterne', 'zem Rade',
        'zem Slüzzel', 'zem guldînen Swerte', 'zer Sunnen', 'zer Krônen', 'zer Rôsen', 'zer Linden',
    ];

    /** Street and house - "Kornmarket, hûs zem Swanen" */
    protected const string STREET_ADDRESS_FORMAT = '{street}, hûs {buildingNumber}';

    /** @var list<string> masks for strings()->numerify() - German postcodes */
    protected const array POSTCODE = ['#####'];

    /** @var list<string> - cellar, upper room, tower, chamber */
    protected const array SECONDARY_ADDRESS = ['keller', 'sölre', 'turn', 'kemenâte'];

    /** @var array<string, string> lands of the Empire */
    protected const array STATES = [
        'BA' => 'Beiern', 'SW' => 'Swâben', 'VR' => 'Vranken', 'SA' => 'Sahsen', 'DU' => 'Düringen',
        'OS' => 'Ôsterrîche', 'EL' => 'Elsaz', 'RI' => 'Rînlant',
    ];

    /**
     * Bishops' sees - dioceses as counties
     *
     * @var list<string>
     */
    protected const array DIOCESES = [
        'Megenze', 'Kölne', 'Triere', 'Wormez', 'Spîre', 'Strâzburc', 'Bazel', 'Kostenze', 'Ougesburc', 'Regensburc',
        'Pazzouwe', 'Salzburc', 'Wirzeburc', 'Babenberc',
    ];

    /** No postcode - "Kornmarket, hûs zem Swanen, Wormez" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [Middle High German name, land code, latitude, longitude] - coordinates of the town today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Wormez', 'RI', 49.632, 8.359], ['Megenze', 'RI', 49.999, 8.273], ['Spîre', 'RI', 49.317, 8.441],
        ['Kölne', 'RI', 50.938, 6.957], ['Ache', 'RI', 50.775, 6.084], ['Santen', 'RI', 51.659, 6.446],
        ['Triere', 'RI', 49.756, 6.641],
        ['Strâzburc', 'EL', 48.582, 7.751], ['Hagenouwe', 'EL', 48.816, 7.790],
        ['Bazel', 'SW', 47.557, 7.588], ['Zürich', 'SW', 47.372, 8.542], ['Kostenze', 'SW', 47.660, 9.176],
        ['Ougesburc', 'SW', 48.371, 10.898], ['Ulme', 'SW', 48.399, 9.993],
        ['Regensburc', 'BA', 49.019, 12.097], ['Pazzouwe', 'BA', 48.574, 13.466], ['Salzburc', 'BA', 47.800, 13.044],
        ['Vrankenvurt', 'VR', 50.110, 8.682], ['Wirzeburc', 'VR', 49.792, 9.932], ['Babenberc', 'VR', 49.892, 10.887],
        ['Nüerenberc', 'VR', 49.454, 11.077],
        ['Isenache', 'DU', 50.975, 10.320], ['Erfurt', 'DU', 50.978, 11.029],
        ['Magdeburc', 'SA', 52.131, 11.640], ['Goslâr', 'SA', 51.906, 10.429], ['Brûneswîc', 'SA', 52.264, 10.524],
        ['Bechelâren', 'OS', 48.212, 15.218], ['Medelicke', 'OS', 48.228, 15.332], ['Tulne', 'OS', 48.331, 16.056],
        ['Wiene', 'OS', 48.208, 16.373],
    ];

    /**
     * A real town of the Empire, e.g. "Wormez" (Worms) - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Kornmarket", "Smidegazze" (smiths' lane)
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * The name of the house - medieval houses had no numbers, e.g. "zem Swanen" (at the Swan), "zer Krônen"
     */
    public function buildingNumber(): string
    {
        return $this->randomizer->randomElement(static::HOUSES);
    }

    /**
     * Cellar, upper room, tower or chamber, e.g. "kemenâte"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Diocese, e.g. "bistuom ze Megenze" (diocese of Mainz)
     */
    public function county(): string
    {
        return 'bistuom ze ' . $this->randomizer->randomElement(static::DIOCESES);
    }

    /**
     * A real town with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Bechelâren',
     * 'state' => 'Ôsterrîche', 'stateCode' => 'OS', 'county' => 'bistuom ze Pazzouwe', 'postcode' => '67547', ...]
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
