<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Medieval addresses: "Chepe, atte signe of the Tabard, Londoun" - houses had signs, not numbers. Streets of
 * medieval London, towns in Middle English spelling with their shire and the coordinates of the town today; parishes
 * as counties. Postcodes are not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Northe', 'Southe', 'Est', 'West', 'Newe', 'Olde'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['tone', 'bury', 'ham', 'wyk', 'chestre', 'feld'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['strete', 'lane', 'gate'];

    /**
     * Streets of medieval London
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Chepe', 'Bredstrete', 'Milkstrete', 'Fletestrete', 'Watlyngstrete', 'Candelwykstrete', 'Gracechirchestrete',
        'Lumbardstrete', 'Tamysestrete', 'Bisshopesgate', 'Aldrichegate', 'Cornhulle', 'Fridaystrete', 'Wodestrete',
        'Hony lane', 'Sopereslane',
    ];

    /**
     * Signs over doors - the address of a house, shop or inn
     *
     * @var list<string>
     */
    protected const array SIGNS = [
        'Tabard', 'Swan', 'Belle', 'Sterre', 'Crowne', 'Rose', 'Whyte Hart', 'Rede Lyoun', 'Grene Dragoun', 'Bore',
        'Egle', 'Cok', 'Sonne', 'Cheker', 'Keye', 'Bulle', 'Lamb', 'Aungel', 'Thre Cranes',
    ];

    /** Street and sign - "Chepe, atte signe of the Tabard" */
    protected const string STREET_ADDRESS_FORMAT = '{street}, atte signe of the {buildingNumber}';

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['shoppe', 'solere', 'seler'];

    /** @var array<string, string> shires */
    protected const array STATES = [
        'KE' => 'Kent', 'MX' => 'Middelsex', 'SY' => 'Surreye', 'BK' => 'Berkshire', 'OX' => 'Oxenfordshire',
        'CB' => 'Cantebriggeshire', 'YK' => 'Yorkshire', 'SO' => 'Somerset', 'HA' => 'Hamtshire', 'WI' => 'Wiltshire',
        'NT' => 'Notynghamshire', 'LI' => 'Lincolnshire', 'NF' => 'Norfolk', 'GL' => 'Gloucestreshire',
        'WA' => 'Warwykshire', 'CH' => 'Chesshire', 'DE' => 'Devenshire', 'HE' => 'Hertfordshire',
        'HF' => 'Herefordshire', 'SH' => 'Shropshire',
    ];

    /**
     * Patron saints of parishes
     *
     * @var list<string>
     */
    protected const array PARISHES = [
        'Seint Marie atte Bowe', 'Seint Martyn', 'Seint Botulf', 'Seint Gyles', 'Seint Michel', 'Seint Andrewe',
        'Seint Laurence', 'Seint Petre', 'Seint Nicholas', 'Seint Margarete', 'Seint Elene', 'Seint Dunstan',
    ];

    /** No postcode - "Chepe, atte signe of the Tabard, Londoun" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [Middle English name, shire code, latitude, longitude] - coordinates of the town today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Londoun', 'MX', 51.512, -0.091], ['Southwerk', 'SY', 51.503, -0.092], ['Caunterbury', 'KE', 51.280, 1.079],
        ['Rouchestre', 'KE', 51.388, 0.507], ['Grenewych', 'KE', 51.482, -0.009], ['Wyndesore', 'BK', 51.483, -0.604],
        ['Oxenford', 'OX', 51.752, -1.258], ['Wodestok', 'OX', 51.848, -1.354], ['Cantebrigge', 'CB', 52.205, 0.119],
        ['Everwik', 'YK', 53.960, -1.081], ['Bathe', 'SO', 51.381, -2.359], ['Wynchestre', 'HA', 51.063, -1.317],
        ['Salesbury', 'WI', 51.069, -1.795], ['Notyngham', 'NT', 52.954, -1.150], ['Lyncolne', 'LI', 53.234, -0.538],
        ['Norwiche', 'NF', 52.630, 1.297], ['Lenne', 'NF', 52.754, 0.396], ['Bawdeswelle', 'NF', 52.733, 1.013],
        ['Glowcestre', 'GL', 51.864, -2.244], ['Bristowe', 'GL', 51.455, -2.588], ['Couentre', 'WA', 52.408, -1.511],
        ['Chestre', 'CH', 53.190, -2.891], ['Excestre', 'DE', 50.722, -3.531], ['Dertemouthe', 'DE', 50.351, -3.579],
        ['Ware', 'HE', 51.811, -0.028], ['Herforde', 'HF', 52.056, -2.716], ['Shrouesbury', 'SH', 52.707, -2.754],
    ];

    /**
     * A real town, e.g. "Caunterbury" - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Chepe" (Cheapside), "Bredstrete"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * The sign of the house - medieval houses had no numbers, e.g. "Tabard", "Whyte Hart"
     */
    public function buildingNumber(): string
    {
        return $this->randomizer->randomElement(static::SIGNS);
    }

    /**
     * Shop, upper room or cellar, e.g. "solere"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Parish, e.g. "the parisshe of Seint Botulf"
     */
    public function county(): string
    {
        return 'the parisshe of ' . $this->randomizer->randomElement(static::PARISHES);
    }

    /**
     * A real town with its shire and coordinates (randomized up to ~300 m), e.g. ['city' => 'Caunterbury',
     * 'state' => 'Kent', 'stateCode' => 'KE', 'county' => 'the parisshe of Seint Dunstan', ...]
     *
     * @param string|null $stateCode only this shire (a code of STATES)
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
