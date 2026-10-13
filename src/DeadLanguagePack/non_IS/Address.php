<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\non_IS;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Addresses of the Norse world: "Mikla gata 12, Jórvík" - towns from Vínland to Miklagarðr under their Old Norse
 * names, with the coordinates of the site today; things (assemblies) as counties. Postcodes are Icelandic ("101") and
 * not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Norðr', 'Suðr', 'Austr', 'Vestr', 'Mikli', 'Litli'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['vík', 'fjǫrðr', 'staðir', 'nes', 'holt', 'dalr'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['gata', 'stræti', 'torg'];

    /**
     * Streets of Norse towns - "gata" lives on in York's Micklegate and Stonegate
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Mikla gata', 'Steingata', 'Fiskigata', 'Skjaldaragata', 'Strandgata', 'Smiðjugata', 'Konungsgata',
        'Kirkjustræti', 'Hafnarstræti', 'Torg', 'Bryggja',
    ];

    /** @var list<string> masks for strings()->numerify() - small towns, small numbers */
    protected const array BUILDING_NUMBER = ['%', '%', '%#'];

    /** @var list<string> masks for strings()->numerify() - Icelandic postcodes */
    protected const array POSTCODE = ['%##'];

    /** @var list<string> masks for strings()->numerify() */
    protected const array SECONDARY_ADDRESS = ['skáli %', 'naust %', 'búð %#'];

    /**
     * Lands of the Norse world
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'IS' => 'Ísland', 'NO' => 'Nóregr', 'DK' => 'Danmǫrk', 'SE' => 'Svíþjóð', 'GL' => 'Grœnland', 'VI' => 'Vínland',
        'OR' => 'Orkneyjar', 'FO' => 'Færeyjar', 'EN' => 'England', 'IR' => 'Írland', 'GA' => 'Garðaríki',
        'MI' => 'Miklagarðr',
    ];

    /**
     * Things - the assemblies where free men met, made law and settled feuds
     *
     * @var list<string>
     */
    protected const array THINGS = [
        'Gulaþing', 'Frostaþing', 'Eiðsifaþing', 'Borgarþing', 'Þórsnessþing', 'Kjalarnessþing', 'Þverárþing',
        'Vaðlaþing', 'Gutnalþing', 'Þingvǫllr',
    ];

    /** No postcode - "Mikla gata 12, Jórvík" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [Old Norse name, land code, latitude, longitude] - coordinates of the site today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Reykjavík', 'IS', 64.146, -21.942], ['Þingvellir', 'IS', 64.256, -21.130], ['Skálholt', 'IS', 64.127, -20.525],
        ['Hólar', 'IS', 65.734, -19.112], ['Borg', 'IS', 64.555, -21.909],
        ['Niðaróss', 'NO', 63.430, 10.395], ['Bjǫrgvin', 'NO', 60.392, 5.324], ['Ósló', 'NO', 59.913, 10.752],
        ['Túnsberg', 'NO', 59.267, 10.408], ['Stafangr', 'NO', 58.970, 5.733],
        ['Heiðabýr', 'DK', 54.491, 9.565], ['Ripar', 'DK', 55.328, 8.762], ['Hróarskelda', 'DK', 55.642, 12.080],
        ['Jalangr', 'DK', 55.756, 9.420], ['Árósar', 'DK', 56.157, 10.211], ['Lundr', 'DK', 55.704, 13.191],
        ['Uppsalir', 'SE', 59.898, 17.633], ['Birka', 'SE', 59.335, 17.542], ['Sigtúnir', 'SE', 59.617, 17.723],
        ['Vísby', 'SE', 57.640, 18.296],
        ['Brattahlíð', 'GL', 61.157, -45.523], ['Garðar', 'GL', 60.993, -45.420],
        ['Leifsbúðir', 'VI', 51.596, -55.533],
        ['Kirkjuvágr', 'OR', 58.981, -2.960], ['Byrgisey', 'OR', 59.130, -3.310],
        ['Þórshǫfn', 'FO', 62.009, -6.772], ['Kirkjubœr', 'FO', 61.950, -6.790],
        ['Jórvík', 'EN', 53.960, -1.081], ['Lundúnir', 'EN', 51.512, -0.091],
        ['Dyflinn', 'IR', 53.344, -6.267], ['Veðrafjǫrðr', 'IR', 52.259, -7.110], ['Hlymrekr', 'IR', 52.664, -8.627],
        ['Hólmgarðr', 'GA', 58.522, 31.275], ['Aldeigjuborg', 'GA', 59.997, 32.296], ['Kœnugarðr', 'GA', 50.450, 30.524],
        ['Miklagarðr', 'MI', 41.008, 28.978],
    ];

    /**
     * A real town of the Norse world, e.g. "Heiðabýr" - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Mikla gata", "Bryggja"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * Thing (assembly) of the area, e.g. "Gulaþing"
     */
    public function county(): string
    {
        return $this->randomizer->randomElement(static::THINGS);
    }

    /**
     * A real town with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Jórvík',
     * 'state' => 'England', 'stateCode' => 'EN', 'county' => 'Gulaþing', 'postcode' => '101', ...]
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
