<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Medieval addresses: "rue de la Harpe, a l'ensaigne du Cerf, Paris" - houses had signs, not numbers. Streets of
 * medieval Paris, towns in Old French spelling with their land and the coordinates of the town today; parishes as
 * counties. Postcodes are French and not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Neuf', 'Viez', 'Bel', 'Grant'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['ville', 'mont', 'val', 'chastel', 'fontaine'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['rue', 'ruele', 'place'];

    /**
     * Streets of medieval Paris
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'rue de la Harpe', 'rue de la Huchete', 'rue Saint Denis', 'rue Saint Jaque', 'rue Saint Honoré',
        'rue Quiquenpoit', 'rue de la Ferronnerie', 'rue de la Buscherie', 'rue Tirechape', 'rue de la Mortelerie',
        'rue aus Fevres', 'Grant Rue', 'Grant Pont', 'Petit Pont',
    ];

    /**
     * Signs over doors - the address of a house, shop or inn
     *
     * @var list<string>
     */
    protected const array SIGNS = [
        'du Cerf', 'du Cygne', 'de l\'Estoile', 'de la Corone', 'de la Rose', 'du Lion d\'or', 'de l\'Escu de France',
        'du Paon', 'de l\'Ange', 'de la Lune', 'du Soleil d\'or', 'des Trois Rois', 'du Chapel Rouge', 'de la Cloche',
    ];

    /** Street and sign - "rue de la Harpe, a l'ensaigne du Cerf" */
    protected const string STREET_ADDRESS_FORMAT = '{street}, a l\'ensaigne {buildingNumber}';

    /** @var list<string> masks for strings()->numerify() - French postcodes */
    protected const array POSTCODE = ['#####'];

    /** @var list<string> - shop, upper room, cellar */
    protected const array SECONDARY_ADDRESS = ['ouvroir', 'solier', 'celier'];

    /** @var array<string, string> lands of the kingdom */
    protected const array STATES = [
        'FR' => 'France', 'CH' => 'Champaigne', 'NO' => 'Normendie', 'BR' => 'Bretaigne', 'BO' => 'Borgoigne',
        'FL' => 'Flandres', 'PI' => 'Picardie', 'AN' => 'Anjou', 'PO' => 'Poitou', 'GU' => 'Guienne',
        'LA' => 'Lengadoc', 'PR' => 'Provence',
    ];

    /**
     * Patron saints of parishes
     *
     * @var list<string>
     */
    protected const array PARISHES = [
        'Saint Germain', 'Saint Severin', 'Saint Jaque', 'Saint Merri', 'Saint Eustache', 'Saint Andri', 'Saint Leu',
        'Saint Gervais', 'Saint Denis', 'Saint Martin', 'Nostre Dame', 'Sainte Genevieve',
    ];

    /** No postcode - "rue de la Harpe, a l'ensaigne du Cerf, Paris" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [Old French name, land code, latitude, longitude] - coordinates of the town today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Paris', 'FR', 48.853, 2.349], ['Saint Denis', 'FR', 48.936, 2.360], ['Chartres', 'FR', 48.448, 1.489],
        ['Orliens', 'FR', 47.902, 1.909], ['Estampes', 'FR', 48.435, 2.162],
        ['Troies', 'CH', 48.297, 4.074], ['Rains', 'CH', 49.258, 4.032], ['Provins', 'CH', 48.560, 3.299],
        ['Roem', 'NO', 49.443, 1.099], ['Caem', 'NO', 49.183, -0.370], ['Baieus', 'NO', 49.276, -0.703],
        ['Nantes', 'BR', 47.218, -1.554], ['Rennes', 'BR', 48.111, -1.680],
        ['Dijon', 'BO', 47.322, 5.041], ['Vezelai', 'BO', 47.466, 3.748], ['Cluigni', 'BO', 46.434, 4.659],
        ['Arras', 'FL', 50.291, 2.778], ['Bruges', 'FL', 51.209, 3.225], ['Gant', 'FL', 51.054, 3.722],
        ['Amiens', 'PI', 49.894, 2.296], ['Biauvais', 'PI', 49.431, 2.081], ['Loon', 'PI', 49.564, 3.620],
        ['Angiers', 'AN', 47.473, -0.551], ['Tors', 'AN', 47.394, 0.685], ['Peitiers', 'PO', 46.580, 0.340],
        ['Bordele', 'GU', 44.838, -0.579], ['Tolose', 'LA', 43.605, 1.444], ['Narbone', 'LA', 43.184, 3.004],
        ['Monpellier', 'LA', 43.611, 3.877], ['Nymes', 'LA', 43.837, 4.360], ['Biaucaire', 'LA', 43.808, 4.644],
        ['Avignon', 'PR', 43.949, 4.806], ['Orenge', 'PR', 44.138, 4.807], ['Arle', 'PR', 43.677, 4.631],
    ];

    /**
     * A real town, e.g. "Troies" (Troyes) - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "rue de la Harpe", "Grant Pont"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * The sign of the house - medieval houses had no numbers, e.g. "du Cerf", "de la Corone"
     */
    public function buildingNumber(): string
    {
        return $this->randomizer->randomElement(static::SIGNS);
    }

    /**
     * Shop, upper room or cellar, e.g. "solier"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Parish, e.g. "la parroisse Saint Severin"
     */
    public function county(): string
    {
        return 'la parroisse ' . $this->randomizer->randomElement(static::PARISHES);
    }

    /**
     * A real town with its land and coordinates (randomized up to ~300 m), e.g. ['city' => 'Troies',
     * 'state' => 'Champaigne', 'stateCode' => 'CH', 'county' => 'la parroisse Saint Leu', ...]
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
