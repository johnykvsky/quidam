<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Greek addresses: "Hodos Tripodon ιβʹ, Athenai" - streets and quarters of Athens, cities of the Greek world from
 * Massalia to Trapezous under their Greek names, with the coordinates of the site today. Building numbers are Greek
 * numerals, counties the ten tribes of Kleisthenes; postcodes are modern Greek ("105 58") and not in address lines.
 */
class Address extends CoreAddress
{
    use GreekNumeralsTrait;

    /** @var list<string> */
    protected const array CITY_PREFIX = ['Nea', 'Palaia', 'Ano', 'Kato', 'Megale', 'Mikra'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['polis', 'ion', 'ai', 'os', 'eia', 'ous'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['Hodos', 'Stoa', 'Agora'];

    /**
     * Streets, roads, stoas and quarters of Athens
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Hodos Panathenaike', 'Hodos Hiera', 'Hodos Tripodon', 'Hodos Peiraike', 'Hodos Akademias', 'Dromos', 'Agora',
        'Stoa Poikile', 'Stoa Basileios', 'Stoa tou Dios', 'Kerameikos', 'Melite', 'Kollytos', 'Koile', 'Skambonidai',
        'Kydathenaion', 'Diomeia', 'Limnai',
    ];

    /** @var list<string> masks for strings()->numerify() - Greek postcodes */
    protected const array POSTCODE = ['%## ##'];

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['oikia', 'oikema', 'ergasterion'];

    /**
     * Regions of the Greek world
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'AT' => 'Attike', 'BO' => 'Boiotia', 'PH' => 'Phokis', 'EU' => 'Euboia', 'PE' => 'Peloponnesos',
        'NE' => 'Nesoi', 'KR' => 'Krete', 'IO' => 'Ionia', 'KA' => 'Karia', 'AI' => 'Aiolis', 'MH' => 'Megale Hellas',
        'SI' => 'Sikelia', 'MA' => 'Makedonia', 'TH' => 'Thrake', 'EP' => 'Epeiros', 'PO' => 'Pontos',
        'KY' => 'Kyrenaike', 'AG' => 'Aigyptos', 'KE' => 'Keltike',
    ];

    /**
     * The ten tribes of Kleisthenes - every Athenian citizen belonged to one
     *
     * @var list<string>
     */
    protected const array TRIBES = [
        'Erechtheis', 'Aigeis', 'Pandionis', 'Leontis', 'Akamantis', 'Oineis', 'Kekropis', 'Hippothontis', 'Aiantis',
        'Antiochis',
    ];

    /** No postcode - "Hodos Tripodon ιβʹ, Athenai" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Cities as [Greek name, region code, latitude, longitude] - coordinates of the site today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Athenai', 'AT', 37.975, 23.726], ['Peiraieus', 'AT', 37.942, 23.646], ['Eleusis', 'AT', 38.041, 23.540],
        ['Marathon', 'AT', 38.153, 23.963], ['Sounion', 'AT', 37.650, 24.025], ['Acharnai', 'AT', 38.083, 23.733],
        ['Thorikos', 'AT', 37.737, 24.050],
        ['Thebai', 'BO', 38.320, 23.319], ['Plataiai', 'BO', 38.218, 23.274], ['Orchomenos', 'BO', 38.494, 22.976],
        ['Tanagra', 'BO', 38.333, 23.534], ['Thespiai', 'BO', 38.304, 23.150],
        ['Delphoi', 'PH', 38.482, 22.501], ['Amphissa', 'PH', 38.528, 22.377],
        ['Chalkis', 'EU', 38.463, 23.595], ['Eretria', 'EU', 38.395, 23.792],
        ['Sparte', 'PE', 37.074, 22.430], ['Argos', 'PE', 37.633, 22.729], ['Korinthos', 'PE', 37.906, 22.879],
        ['Olympia', 'PE', 37.638, 21.630], ['Mykenai', 'PE', 37.731, 22.756], ['Tiryns', 'PE', 37.600, 22.800],
        ['Epidauros', 'PE', 37.596, 23.079], ['Megalopolis', 'PE', 37.401, 22.141], ['Messene', 'PE', 37.175, 21.917],
        ['Aigina', 'NE', 37.747, 23.428], ['Delos', 'NE', 37.396, 25.268], ['Naxos', 'NE', 37.105, 25.376],
        ['Paros', 'NE', 37.085, 25.150], ['Melos', 'NE', 36.725, 24.420], ['Samos', 'NE', 37.690, 26.942],
        ['Chios', 'NE', 38.370, 26.136], ['Rhodos', 'NE', 36.434, 28.217], ['Mytilene', 'NE', 39.110, 26.555],
        ['Thasos', 'NE', 40.780, 24.710], ['Korkyra', 'NE', 39.624, 19.921],
        ['Knossos', 'KR', 35.298, 25.163], ['Gortyn', 'KR', 35.062, 24.947], ['Kydonia', 'KR', 35.514, 24.018],
        ['Miletos', 'IO', 37.530, 27.278], ['Ephesos', 'IO', 37.940, 27.341], ['Smyrne', 'IO', 38.419, 27.129],
        ['Phokaia', 'IO', 38.669, 26.756], ['Priene', 'IO', 37.659, 27.297], ['Teos', 'IO', 38.177, 26.785],
        ['Halikarnassos', 'KA', 37.038, 27.424], ['Knidos', 'KA', 36.686, 27.374],
        ['Pergamon', 'AI', 39.132, 27.184], ['Kyme', 'AI', 38.762, 26.937],
        ['Taras', 'MH', 40.464, 17.247], ['Neapolis', 'MH', 40.852, 14.268], ['Kroton', 'MH', 39.081, 17.127],
        ['Sybaris', 'MH', 39.717, 16.493], ['Rhegion', 'MH', 38.111, 15.648], ['Elea', 'MH', 40.160, 15.154],
        ['Poseidonia', 'MH', 40.420, 15.005],
        ['Syrakousai', 'SI', 37.075, 15.287], ['Akragas', 'SI', 37.311, 13.577], ['Gela', 'SI', 37.066, 14.250],
        ['Selinous', 'SI', 37.584, 12.825], ['Zankle', 'SI', 38.193, 15.554], ['Katane', 'SI', 37.502, 15.087],
        ['Pella', 'MA', 40.756, 22.525], ['Aigai', 'MA', 40.479, 22.318], ['Amphipolis', 'MA', 40.822, 23.847],
        ['Thessalonike', 'MA', 40.640, 22.944], ['Dion', 'MA', 40.173, 22.490], ['Philippoi', 'MA', 41.013, 24.284],
        ['Byzantion', 'TH', 41.008, 28.978], ['Abdera', 'TH', 40.940, 24.973], ['Ainos', 'TH', 40.725, 26.085],
        ['Dodone', 'EP', 39.546, 20.788], ['Ambrakia', 'EP', 39.157, 20.985],
        ['Sinope', 'PO', 42.026, 35.151], ['Trapezous', 'PO', 41.003, 39.717], ['Olbia', 'PO', 46.690, 31.900],
        ['Pantikapaion', 'PO', 45.350, 36.470], ['Chersonesos', 'PO', 44.611, 33.493],
        ['Kyrene', 'KY', 32.825, 21.858], ['Euesperides', 'KY', 32.120, 20.070],
        ['Alexandreia', 'AG', 31.200, 29.918], ['Naukratis', 'AG', 30.900, 30.592],
        ['Massalia', 'KE', 43.296, 5.370], ['Nikaia', 'KE', 43.703, 7.266], ['Antipolis', 'KE', 43.580, 7.125],
        ['Emporion', 'KE', 42.134, 3.120],
    ];

    /**
     * A real city of the Greek world, e.g. "Miletos" - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Hodos Tripodon", "Stoa Poikile"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * Greek numeral up to ρϟθʹ (199), small numbers more often, e.g. "ιβʹ"
     */
    public function buildingNumber(): string
    {
        return $this->greekNumeral($this->randomizer->getInt(1, $this->randomizer->randomElement([20, 50, 199])));
    }

    /**
     * House, room or workshop with a Greek numeral, e.g. "oikema δʹ"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS) . ' ' . $this->greekNumeral($this->randomizer->getInt(1, 12));
    }

    /**
     * Tribe, e.g. "phyle Erechtheis"
     */
    public function county(): string
    {
        return 'phyle ' . $this->randomizer->randomElement(static::TRIBES);
    }

    /**
     * A real city with its region and coordinates (randomized up to ~300 m), e.g. ['city' => 'Ephesos',
     * 'state' => 'Ionia', 'stateCode' => 'IO', 'county' => 'phyle Aigeis', 'postcode' => '105 58', ...]
     *
     * @param string|null $stateCode only this region (a code of STATES)
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
