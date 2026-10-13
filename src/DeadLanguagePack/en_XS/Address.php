<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Shakespeare's world: "Bread Street, at the sign of the Mermaid, London" - houses known by their signs, streets of
 * Elizabethan London and Stratford, towns of England and the settings of the plays (Verona, Elsinore, Dunsinane),
 * with coordinates of the place today; parishes as counties. Postcodes are not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array STREET_SUFFIX = ['Street', 'Lane', 'Churchyard'];

    /**
     * Streets of Shakespeare's London and Stratford - Henley Street (his birthplace), Silver Street (his lodging),
     * Bread Street (the Mermaid tavern), Maid Lane (the Globe)
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Bankside', 'Cheapside', 'Eastcheap', 'Fleet Street', 'Bread Street', 'Silver Street', 'Henley Street',
        'Chapel Street', 'Bishopsgate', 'Shoreditch', 'Blackfriars', "Paul's Churchyard", 'Maid Lane',
        'Gracechurch Street',
    ];

    /**
     * Signs of taverns, inns and booksellers' shops
     *
     * @var list<string>
     */
    protected const array SIGNS = [
        'Mermaid', "Boar's Head", 'Green Dragon', 'White Greyhound', 'Bell', 'Red Lion', 'Swan', 'Rose', 'Garter',
        'Angel', 'Bear', 'Crown', 'Spread Eagle', 'Bull', 'Star', 'Three Tuns', "Cardinal's Hat",
    ];

    /** Street and sign - "Bread Street, at the sign of the Mermaid" */
    protected const string STREET_ADDRESS_FORMAT = '{street}, at the sign of the {buildingNumber}';

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['shop', 'garret', 'cellar', 'tiring-house'];

    /**
     * English shires and the lands of the plays
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'WA' => 'Warwickshire', 'MX' => 'Middlesex', 'SY' => 'Surrey', 'BK' => 'Berkshire', 'KE' => 'Kent',
        'SC' => 'Scotland', 'DK' => 'Denmark', 'IT' => 'Italy', 'SI' => 'Sicily', 'GR' => 'Greece', 'CY' => 'Cyprus',
        'BO' => 'Bohemia', 'NA' => 'Navarre', 'AM' => 'Asia Minor',
    ];

    /** @var list<string> */
    protected const array PARISHES = [
        "St. Saviour's", "St. Helen's, Bishopsgate", "St. Olave's", 'Holy Trinity', "St. Paul's", 'St. Mary-le-Bow',
        'St. Giles, Cripplegate', "St. Bride's",
    ];

    /** No postcode - "Bread Street, at the sign of the Mermaid, London" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [name, land code, latitude, longitude] - Shakespeare's England and the settings of his plays
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Stratford-upon-Avon', 'WA', 52.192, -1.707], ['Warwick', 'WA', 52.282, -1.585], ['Coventry', 'WA', 52.408, -1.511],
        ['London', 'MX', 51.512, -0.091], ['Southwark', 'SY', 51.503, -0.092], ['Windsor', 'BK', 51.483, -0.604],
        ['Dover', 'KE', 51.126, 1.313], ['Canterbury', 'KE', 51.280, 1.079],
        ['Inverness', 'SC', 57.478, -4.225], ['Dunsinane', 'SC', 56.486, -3.268], ['Forres', 'SC', 57.609, -3.613],
        ['Elsinore', 'DK', 56.036, 12.613],
        ['Verona', 'IT', 45.438, 10.992], ['Venice', 'IT', 45.434, 12.338], ['Padua', 'IT', 45.406, 11.877],
        ['Mantua', 'IT', 45.156, 10.791], ['Milan', 'IT', 45.464, 9.190], ['Rome', 'IT', 41.893, 12.483],
        ['Messina', 'SI', 38.193, 15.554], ['Syracuse', 'SI', 37.075, 15.287],
        ['Athens', 'GR', 37.975, 23.726], ['Famagusta', 'CY', 35.125, 33.941], ['Prague', 'BO', 50.088, 14.420],
        ['Pamplona', 'NA', 42.812, -1.645], ['Ephesus', 'AM', 37.940, 27.341],
    ];

    /**
     * e.g. "Verona"
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Eastcheap"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * The sign of the house - there were no numbers, e.g. "Mermaid", "Boar's Head"
     */
    public function buildingNumber(): string
    {
        return $this->randomizer->randomElement(static::SIGNS);
    }

    /**
     * e.g. "garret", "tiring-house" (where players dress)
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Parish, e.g. "the parish of St. Saviour's"
     */
    public function county(): string
    {
        return 'the parish of ' . $this->randomizer->randomElement(static::PARISHES);
    }

    /**
     * A town with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Verona', 'state' => 'Italy',
     * 'stateCode' => 'IT', 'county' => "the parish of St. Olave's", ...]
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
