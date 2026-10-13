<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\ang_GB;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Anglo-Saxon addresses: "Hēahstrǣt fīf, Wintanceaster" - roads and streets, building numbers in Old English words
 * (one to twelve), towns of the seven kingdoms under their Old English names with their shire and the coordinates of
 * the town today. Postcodes are not in address lines.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Norþ', 'Sūþ', 'Ēast', 'West', 'Niwe', 'Eald'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['tūn', 'burh', 'hām', 'wīc', 'ceaster', 'feld'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['strǣt', 'weg', 'stīg'];

    /**
     * Roman roads under their Old English names (Watling Street, Ermine Street, the Fosse Way, the Icknield Way) and
     * streets of towns
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Wæclinga strǣt', 'Earninga strǣt', 'Fōsweg', 'Icenhilde weg', 'Hēahstrǣt', 'Ēastcēap', 'Westcēap',
        'Cyricstīg', 'Mylenweg', 'Brycgstrǣt',
    ];

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['heall', 'būr', 'cyte'];

    /**
     * The seven kingdoms (the Heptarchy)
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'WS' => 'Westseaxe', 'ME' => 'Mierce', 'NH' => 'Norþhymbre', 'EA' => 'Ēastengle', 'KE' => 'Cent',
        'ES' => 'Ēastseaxe', 'SS' => 'Sūþseaxe',
    ];

    /** No postcode - "Hēahstrǣt fīf, Wintanceaster" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Towns as [Old English name, kingdom code, shire, latitude, longitude] - coordinates of the town today. The north
     * had no shires before the Conquest - Bernice and Dere were its two old kingdoms.
     *
     * @var list<array{string, string, string, float, float}>
     */
    protected const array PLACES = [
        ['Wintanceaster', 'WS', 'Hamtūnscīr', 51.063, -1.317], ['Hamwīc', 'WS', 'Hamtūnscīr', 50.904, -1.404],
        ['Exanceaster', 'WS', 'Defenascīr', 50.722, -3.531], ['Baþ', 'WS', 'Sumorsǣte', 51.381, -2.359],
        ['Oxenaford', 'WS', 'Oxenafordscīr', 51.752, -1.258], ['Sceaftesburh', 'WS', 'Dornsǣte', 51.005, -2.198],
        ['Lundenwīc', 'ME', 'Middelseaxe', 51.512, -0.091], ['Tamworþig', 'ME', 'Stæfordscīr', 52.633, -1.695],
        ['Wigoraceaster', 'ME', 'Wigoraceasterscīr', 52.192, -2.220],
        ['Glēawceaster', 'ME', 'Glēawceasterscīr', 51.864, -2.244],
        ['Snotingahām', 'ME', 'Snotingahāmscīr', 52.954, -1.150], ['Dēoraby', 'ME', 'Dēorabyscīr', 52.922, -1.476],
        ['Ligeraceaster', 'ME', 'Ligeraceasterscīr', 52.636, -1.139], ['Lindcylene', 'ME', 'Lindesīg', 53.234, -0.538],
        ['Scrobbesbyrig', 'ME', 'Scrobbesbyrigscīr', 52.707, -2.754],
        ['Eoforwīc', 'NH', 'Dere', 53.960, -1.081], ['Bebbanburh', 'NH', 'Bernice', 55.609, -1.710],
        ['Lindisfarnea', 'NH', 'Bernice', 55.669, -1.801], ['Gyrwe', 'NH', 'Bernice', 54.980, -1.484],
        ['Wiremūþa', 'NH', 'Bernice', 54.913, -1.379],
        ['Grantabrycg', 'EA', 'Grantabrycgscīr', 52.205, 0.119], ['Gipeswīc', 'EA', 'Sūþfolc', 52.059, 1.155],
        ['Norþwīc', 'EA', 'Norþfolc', 52.630, 1.297],
        ['Cantwaraburh', 'KE', 'Cent', 51.280, 1.079], ['Hrofesceaster', 'KE', 'Cent', 51.388, 0.507],
        ['Dofras', 'KE', 'Cent', 51.126, 1.313],
        ['Mældūn', 'ES', 'Ēastseaxe', 51.731, 0.677], ['Colneceaster', 'ES', 'Ēastseaxe', 51.889, 0.904],
        ['Ciseceaster', 'SS', 'Sūþseaxe', 50.837, -0.779], ['Hæstingas', 'SS', 'Sūþseaxe', 50.854, 0.573],
        ['Seolesīg', 'SS', 'Sūþseaxe', 50.733, -0.790],
    ];

    /** Building numbers - one to twelve */
    private const array NUMBERS = [
        'ān', 'twā', 'þrēo', 'fēower', 'fīf', 'siex', 'seofon', 'eahta', 'nigon', 'tīen', 'endleofan', 'twelf',
    ];

    /**
     * A real town, e.g. "Eoforwīc" (York) - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Wæclinga strǣt" (Watling Street)
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * One to twelve in Old English, e.g. "fīf"
     */
    public function buildingNumber(): string
    {
        return $this->randomizer->randomElement(self::NUMBERS);
    }

    /**
     * Hall, bower or cottage, e.g. "būr"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS);
    }

    /**
     * Shire of a random town, e.g. "Hamtūnscīr"
     */
    public function county(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[2];
    }

    /**
     * A real town with its kingdom, shire and coordinates (randomized up to ~300 m), e.g. ['city' => 'Eoforwīc',
     * 'state' => 'Norþhymbre', 'stateCode' => 'NH', 'county' => 'Dere', ...]
     *
     * @param string|null $stateCode only this kingdom (a code of STATES)
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

        [$city, $code, $shire, $latitude, $longitude] = $this->randomizer->randomElement($places);

        return [
            'city' => $city,
            'state' => static::STATES[$code],
            'stateCode' => $code,
            'county' => $shire,
            'postcode' => $this->postcode(),
            'latitude' => round($latitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
            'longitude' => round($longitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
        ];
    }
}
