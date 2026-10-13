<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use Quidam\Core\Address as CoreAddress;
use Quidam\Definitions\Enum\Gender;
use Quidam\Exception\InvalidArgumentException;

/**
 * Addresses of Mesopotamia: "sūq Šamaš, bīt Ea-nāṣir, Ur" - streets of the gods, and houses known by their owners, as
 * sale contracts describe them. Cities from Ur to Nineveh, Mari and Kaneš, with the coordinates of the site today;
 * temples as counties. Postcodes are Iraqi and not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Dūr', 'Kār', 'Āl'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['-Šamaš', '-Sîn', '-Marduk', '-Ištar'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['sūqum', 'sulûm'];

    /**
     * Streets - of the gods, the broad street, the merchants' quay, and Babylon's Processional Way "may the arrogant
     * not flourish"
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Ay-ibūr-šābû', 'sūq Marduk', 'sūq Ištar', 'sūq Šamaš', 'sūq Adad', 'sūq Nabû', 'sūq Sîn', 'sūqum rapšum',
        'sūq tamkārī', 'kārum',
    ];

    /** House and owner - "sūq Šamaš, bīt Ea-nāṣir" */
    protected const string STREET_ADDRESS_FORMAT = '{street}, {buildingNumber}';

    /** @var list<string> masks for strings()->numerify() - Iraqi postcodes */
    protected const array POSTCODE = ['#####'];

    /** @var list<string> - roof, courtyard, inner room */
    protected const array SECONDARY_ADDRESS = ['ūrum', 'tarbaṣum', 'kummum'];

    /**
     * Lands as Akkadian named them
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'SU' => 'Šumerum', 'AK' => 'Akkadum', 'AS' => 'Aššur', 'MA' => 'Mari', 'HA' => 'Ḫatti', 'EL' => 'Elamtum',
    ];

    /**
     * Temples - their Sumerian names, "house of ..."
     *
     * @var list<string>
     */
    protected const array TEMPLES = [
        'Esagil', 'Ezida', 'Ekur', 'Eanna', 'Ebabbar', 'Ekišnugal', 'Eninnu', 'Emeslam', 'Ešarra', 'Egalmaḫ',
    ];

    /** No postcode - "sūq Šamaš, bīt Ea-nāṣir, Ur" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Cities as [name, land code, latitude, longitude] - coordinates of the site today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Ur', 'SU', 30.962, 46.103], ['Uruk', 'SU', 31.322, 45.636], ['Eridu', 'SU', 30.816, 45.996],
        ['Larsa', 'SU', 31.286, 45.854], ['Lagaš', 'SU', 31.413, 46.399], ['Girsu', 'SU', 31.551, 46.180],
        ['Umma', 'SU', 31.664, 45.888], ['Nippur', 'SU', 32.127, 45.231], ['Isin', 'SU', 31.886, 45.270],
        ['Bābilim', 'AK', 32.536, 44.421], ['Sippar', 'AK', 33.060, 44.252], ['Kiš', 'AK', 32.540, 44.605],
        ['Barsipa', 'AK', 32.392, 44.341], ['Kutha', 'AK', 32.750, 44.620], ['Ešnunna', 'AK', 33.484, 44.729],
        ['Aššur', 'AS', 35.456, 43.262], ['Ninua', 'AS', 36.359, 43.153], ['Kalḫu', 'AS', 36.098, 43.329],
        ['Dūr-Šarrukīn', 'AS', 36.509, 43.229], ['Arbail', 'AS', 36.191, 44.009],
        ['Mari', 'MA', 34.550, 40.889], ['Terqa', 'MA', 34.909, 40.578], ['Ebla', 'MA', 35.798, 36.798],
        ['Kaneš', 'HA', 38.850, 35.635], ['Ḫattuš', 'HA', 40.020, 34.615],
        ['Šušan', 'EL', 32.190, 48.258], ['Anšan', 'EL', 30.008, 52.408],
    ];

    /**
     * A real city, e.g. "Bābilim" (Babylon) - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "sūq Šamaš" (street of Šamaš), "kārum" (the merchants' quay)
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * The house of its owner - contracts describe houses by owner and neighbours, e.g. "bīt Ea-nāṣir"
     */
    public function buildingNumber(): string
    {
        return 'bīt ' . $this->person->firstName(Gender::Male);
    }

    /**
     * Roof, courtyard or inner room, e.g. "tarbaṣum"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Temple of the district, e.g. "Esagil" (Marduk's temple in Babylon)
     */
    public function county(): string
    {
        return $this->randomizer->randomElement(static::TEMPLES);
    }

    /**
     * A real city with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Ninua',
     * 'state' => 'Aššur', 'stateCode' => 'AS', 'county' => 'Esagil', 'postcode' => '10013', ...]
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
