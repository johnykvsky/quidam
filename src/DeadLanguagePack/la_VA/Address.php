<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\la_VA;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Roman addresses: "Via Sacra XLII, Londinium" - real streets of Rome, cities of the provinces under their Latin
 * names, with the coordinates of the place today. Romans had no postcodes: postcode() is the Vatican's 00120 and the
 * address line has none. Building numbers are Roman numerals, counties are the 35 tribes of Roman citizens.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Aquae', 'Augusta', 'Castra', 'Colonia', 'Forum', 'Portus'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['ium', 'um', 'ia', 'ae', 'acum', 'odunum'];

    /** @var list<string> */
    protected const array STREET_SUFFIX = ['Via', 'Vicus', 'Clivus'];

    /**
     * Streets of Rome and roads of the empire
     *
     * @var list<string>
     */
    protected const array STREETS = [
        'Via Appia', 'Via Sacra', 'Via Flaminia', 'Via Aurelia', 'Via Salaria', 'Via Latina', 'Via Nova', 'Via Lata',
        'Via Ostiensis', 'Via Tiburtina', 'Via Praenestina', 'Via Labicana', 'Via Nomentana', 'Via Cassia',
        'Via Aemilia', 'Via Domitia', 'Via Egnatia', 'Vicus Tuscus', 'Vicus Iugarius', 'Vicus Longus',
        'Vicus Patricius', 'Vicus Sandaliarius', 'Vicus Unguentarius', 'Clivus Capitolinus', 'Clivus Palatinus',
        'Clivus Suburanus', 'Clivus Victoriae', 'Argiletum', 'Subura', 'Forum Boarium', 'Forum Holitorium',
    ];

    /** @var list<string> */
    protected const array POSTCODE = ['00120'];

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['insula', 'cenaculum', 'taberna'];

    /**
     * Provinces (and Italy) of the 2nd century
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'IT' => 'Italia', 'SI' => 'Sicilia', 'BR' => 'Britannia', 'GN' => 'Gallia Narbonensis',
        'GL' => 'Gallia Lugdunensis', 'GI' => 'Germania Inferior', 'GS' => 'Germania Superior', 'RA' => 'Raetia',
        'HT' => 'Hispania Tarraconensis', 'BA' => 'Baetica', 'LU' => 'Lusitania', 'AF' => 'Africa Proconsularis',
        'AE' => 'Aegyptus', 'SY' => 'Syria', 'IU' => 'Iudaea', 'AS' => 'Asia', 'AC' => 'Achaia', 'MC' => 'Macedonia',
        'DL' => 'Dalmatia', 'PA' => 'Pannonia', 'DA' => 'Dacia',
    ];

    /**
     * Tribes - every Roman citizen was enrolled in one
     *
     * @var list<string>
     */
    protected const array TRIBES = [
        'Aemilia', 'Aniensis', 'Arnensis', 'Camilia', 'Claudia', 'Clustumina', 'Collina', 'Cornelia', 'Esquilina',
        'Fabia', 'Falerna', 'Galeria', 'Horatia', 'Lemonia', 'Maecia', 'Menenia', 'Oufentina', 'Palatina', 'Papiria',
        'Pollia', 'Pomptina', 'Publilia', 'Pupinia', 'Quirina', 'Romilia', 'Sabatina', 'Scaptia', 'Sergia',
        'Stellatina', 'Suburana', 'Teretina', 'Tromentina', 'Velina', 'Voltinia', 'Voturia',
    ];

    /** No postcode - "Via Sacra XLII, Londinium" */
    protected const string ADDRESS_FORMAT = '{street}, {city}';

    /**
     * Cities as [Latin name, province code, latitude, longitude] - coordinates of the site today
     *
     * @var list<array{string, string, float, float}>
     */
    protected const array PLACES = [
        ['Roma', 'IT', 41.893, 12.483], ['Ostia', 'IT', 41.756, 12.292], ['Pompeii', 'IT', 40.749, 14.485],
        ['Herculaneum', 'IT', 40.806, 14.348], ['Neapolis', 'IT', 40.852, 14.268], ['Puteoli', 'IT', 40.823, 14.122],
        ['Capua', 'IT', 41.083, 14.250], ['Mediolanum', 'IT', 45.464, 9.190], ['Ravenna', 'IT', 44.418, 12.204],
        ['Verona', 'IT', 45.438, 10.992], ['Aquileia', 'IT', 45.770, 13.367], ['Brundisium', 'IT', 40.638, 17.946],
        ['Tarentum', 'IT', 40.464, 17.247], ['Florentia', 'IT', 43.770, 11.256], ['Genua', 'IT', 44.407, 8.934],
        ['Ariminum', 'IT', 44.059, 12.568],
        ['Syracusae', 'SI', 37.075, 15.287], ['Panormus', 'SI', 38.116, 13.361], ['Messana', 'SI', 38.193, 15.554],
        ['Agrigentum', 'SI', 37.311, 13.577], ['Catina', 'SI', 37.502, 15.087],
        ['Londinium', 'BR', 51.512, -0.091], ['Eboracum', 'BR', 53.960, -1.081], ['Deva', 'BR', 53.190, -2.891],
        ['Aquae Sulis', 'BR', 51.381, -2.359], ['Camulodunum', 'BR', 51.889, 0.904], ['Lindum', 'BR', 53.234, -0.538],
        ['Isca', 'BR', 50.722, -3.531], ['Verulamium', 'BR', 51.750, -0.354],
        ['Narbo', 'GN', 43.184, 3.004], ['Massilia', 'GN', 43.296, 5.370], ['Nemausus', 'GN', 43.837, 4.360],
        ['Arelate', 'GN', 43.677, 4.630], ['Tolosa', 'GN', 43.605, 1.444], ['Vienna', 'GN', 45.525, 4.874],
        ['Forum Iulii', 'GN', 43.433, 6.737],
        ['Lugdunum', 'GL', 45.760, 4.835], ['Lutetia', 'GL', 48.853, 2.349], ['Augustodunum', 'GL', 46.951, 4.299],
        ['Rotomagus', 'GL', 49.443, 1.099], ['Cenabum', 'GL', 47.903, 1.909], ['Condate', 'GL', 48.111, -1.680],
        ['Colonia Agrippina', 'GI', 50.938, 6.960], ['Bonna', 'GI', 50.733, 7.100], ['Novaesium', 'GI', 51.200, 6.692],
        ['Vetera', 'GI', 51.659, 6.446], ['Traiectum', 'GI', 52.091, 5.122], ['Noviomagus', 'GI', 51.842, 5.853],
        ['Mogontiacum', 'GS', 49.999, 8.274], ['Argentoratum', 'GS', 48.582, 7.751],
        ['Augusta Raurica', 'GS', 47.534, 7.722], ['Vindonissa', 'GS', 47.481, 8.213], ['Aventicum', 'GS', 46.881, 7.042],
        ['Augusta Vindelicum', 'RA', 48.370, 10.898], ['Castra Regina', 'RA', 49.013, 12.101],
        ['Cambodunum', 'RA', 47.727, 10.313],
        ['Tarraco', 'HT', 41.119, 1.245], ['Caesaraugusta', 'HT', 41.649, -0.889], ['Barcino', 'HT', 41.384, 2.177],
        ['Carthago Nova', 'HT', 37.605, -0.986], ['Legio', 'HT', 42.599, -5.567], ['Toletum', 'HT', 39.857, -4.024],
        ['Corduba', 'BA', 37.885, -4.779], ['Hispalis', 'BA', 37.389, -5.984], ['Gades', 'BA', 36.527, -6.289],
        ['Italica', 'BA', 37.443, -6.046], ['Malaca', 'BA', 36.721, -4.421],
        ['Emerita Augusta', 'LU', 38.916, -6.344], ['Olisipo', 'LU', 38.722, -9.139], ['Conimbriga', 'LU', 40.100, -8.493],
        ['Pax Iulia', 'LU', 38.015, -7.863],
        ['Carthago', 'AF', 36.853, 10.323], ['Utica', 'AF', 37.057, 10.062], ['Hadrumetum', 'AF', 35.826, 10.637],
        ['Thysdrus', 'AF', 35.296, 10.707], ['Leptis Magna', 'AF', 32.638, 14.293], ['Sabratha', 'AF', 32.805, 12.485],
        ['Hippo Regius', 'AF', 36.884, 7.751],
        ['Alexandria', 'AE', 31.200, 29.918], ['Memphis', 'AE', 29.845, 31.251], ['Thebae', 'AE', 25.700, 32.639],
        ['Pelusium', 'AE', 31.042, 32.545], ['Antinoopolis', 'AE', 27.810, 30.878],
        ['Antiochia', 'SY', 36.202, 36.160], ['Damascus', 'SY', 33.511, 36.306], ['Palmyra', 'SY', 34.551, 38.269],
        ['Berytus', 'SY', 33.894, 35.503], ['Apamea', 'SY', 35.418, 36.398],
        ['Hierosolyma', 'IU', 31.778, 35.235], ['Caesarea Maritima', 'IU', 32.500, 34.892], ['Ioppe', 'IU', 32.054, 34.752],
        ['Ephesus', 'AS', 37.940, 27.341], ['Pergamum', 'AS', 39.132, 27.184], ['Smyrna', 'AS', 38.419, 27.129],
        ['Miletus', 'AS', 37.530, 27.278], ['Sardes', 'AS', 38.488, 28.040], ['Halicarnassus', 'AS', 37.038, 27.424],
        ['Athenae', 'AC', 37.976, 23.726], ['Corinthus', 'AC', 37.906, 22.879], ['Sparta', 'AC', 37.074, 22.430],
        ['Delphi', 'AC', 38.482, 22.501], ['Patrae', 'AC', 38.246, 21.735],
        ['Thessalonica', 'MC', 40.640, 22.944], ['Philippi', 'MC', 41.013, 24.284], ['Pella', 'MC', 40.756, 22.525],
        ['Dyrrachium', 'MC', 41.323, 19.441],
        ['Salona', 'DL', 43.539, 16.484], ['Iader', 'DL', 44.116, 15.228], ['Narona', 'DL', 43.080, 17.625],
        ['Epidaurum', 'DL', 42.581, 18.218],
        ['Aquincum', 'PA', 47.564, 19.049], ['Carnuntum', 'PA', 48.111, 16.863], ['Vindobona', 'PA', 48.209, 16.370],
        ['Sirmium', 'PA', 44.969, 19.610], ['Savaria', 'PA', 47.231, 16.621], ['Siscia', 'PA', 45.483, 16.373],
        ['Poetovio', 'PA', 46.420, 15.870],
        ['Sarmizegetusa', 'DA', 45.516, 22.788], ['Apulum', 'DA', 46.069, 23.580], ['Napoca', 'DA', 46.770, 23.590],
        ['Porolissum', 'DA', 47.179, 23.150], ['Potaissa', 'DA', 46.561, 23.762],
    ];

    /**
     * A real city of the empire, e.g. "Lutetia" - addresses use real places only
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * e.g. "Via Appia", "Clivus Capitolinus"
     */
    public function streetName(): string
    {
        return $this->randomizer->randomElement(static::STREETS);
    }

    /**
     * Roman numeral up to CXCIX (199), small numbers more often, e.g. "XLII"
     */
    public function buildingNumber(): string
    {
        return $this->roman($this->randomizer->getInt(1, $this->randomizer->randomElement([20, 50, 199])));
    }

    /**
     * Block, upper-floor flat or shop with a Roman numeral, e.g. "cenaculum IV"
     */
    public function secondaryAddress(): string
    {
        return $this->randomizer->randomElement(static::SECONDARY_ADDRESS) . ' ' . $this->roman($this->randomizer->getInt(1, 12));
    }

    /**
     * Tribe, e.g. "tribus Palatina"
     */
    public function county(): string
    {
        return 'tribus ' . $this->randomizer->randomElement(static::TRIBES);
    }

    /**
     * A real city with its province and coordinates (randomized up to ~300 m), e.g. ['city' => 'Eboracum',
     * 'state' => 'Britannia', 'stateCode' => 'BR', 'county' => 'tribus Quirina', 'postcode' => '00120', ...]
     *
     * @param string|null $stateCode only this province (a code of STATES)
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

    private function roman(int $number): string
    {
        $roman = '';
        foreach (['C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }
}
