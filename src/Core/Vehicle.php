<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Manufacturers and models, VIN with valid check digit, licence plates
 */
class Vehicle implements VehicleExtensionInterface
{
    /**
     * Manufacturer => real World Manufacturer Identifiers (first 3 VIN characters) and models
     *
     * @var array<string, array{wmi: list<string>, models: list<string>}>
     */
    protected const array MANUFACTURERS = [
        'Audi' => ['wmi' => ['WAU', 'WA1'], 'models' => ['A3', 'A4', 'A6', 'Q5', 'Q7']],
        'BMW' => ['wmi' => ['WBA', 'WBS', '5UX'], 'models' => ['3 Series', '5 Series', 'X3', 'X5', 'i4']],
        'Chevrolet' => ['wmi' => ['1G1', '2G1', '3G1'], 'models' => ['Malibu', 'Camaro', 'Silverado', 'Equinox', 'Tahoe']],
        'Fiat' => ['wmi' => ['ZFA'], 'models' => ['500', 'Panda', 'Tipo']],
        'Ford' => ['wmi' => ['1FA', '1FT', '3FA', 'WF0'], 'models' => ['Focus', 'Fiesta', 'Mustang', 'F-150', 'Explorer', 'Kuga']],
        'Honda' => ['wmi' => ['JHM', '1HG', '2HG'], 'models' => ['Civic', 'Accord', 'CR-V', 'Jazz', 'HR-V']],
        'Hyundai' => ['wmi' => ['KMH', '5NP'], 'models' => ['Elantra', 'Tucson', 'i30', 'Kona', 'Santa Fe']],
        'Kia' => ['wmi' => ['KNA', 'KND', '5XY'], 'models' => ['Sportage', 'Ceed', 'Rio', 'Sorento', 'EV6']],
        'Mazda' => ['wmi' => ['JM1', 'JM3'], 'models' => ['Mazda3', 'CX-5', 'MX-5']],
        'Mercedes-Benz' => ['wmi' => ['WDD', 'WDB', '4JG'], 'models' => ['A-Class', 'C-Class', 'E-Class', 'S-Class', 'GLC']],
        'Nissan' => ['wmi' => ['JN1', '1N4', '3N1'], 'models' => ['Qashqai', 'Altima', 'Leaf', 'Micra', 'Rogue']],
        'Peugeot' => ['wmi' => ['VF3'], 'models' => ['208', '308', '3008']],
        'Renault' => ['wmi' => ['VF1'], 'models' => ['Clio', 'Megane', 'Captur']],
        'Skoda' => ['wmi' => ['TMB'], 'models' => ['Octavia', 'Fabia', 'Superb', 'Kodiaq']],
        'Subaru' => ['wmi' => ['JF1', '4S3'], 'models' => ['Outback', 'Forester', 'Impreza']],
        'Tesla' => ['wmi' => ['5YJ', '7SA'], 'models' => ['Model 3', 'Model Y', 'Model S', 'Model X']],
        'Toyota' => ['wmi' => ['JTD', 'JTE', '4T1', '2T1'], 'models' => ['Corolla', 'Camry', 'RAV4', 'Yaris', 'Prius', 'Highlander']],
        'Volkswagen' => ['wmi' => ['WVW', 'WV2', '3VW'], 'models' => ['Golf', 'Passat', 'Polo', 'Tiguan', 'ID.4']],
        'Volvo' => ['wmi' => ['YV1', 'YV4'], 'models' => ['XC40', 'XC60', 'XC90', 'V60']],
    ];

    /** @var list<string> */
    protected const array TYPES = [
        'Sedan', 'Hatchback', 'SUV', 'Crossover', 'Coupe', 'Convertible', 'Wagon', 'Pickup', 'Van', 'Minivan',
    ];

    /** @var array<string, int> fuel => weight */
    protected const array FUELS = [
        'Gasoline' => 45, 'Diesel' => 20, 'Hybrid' => 15, 'Electric' => 12, 'Plug-in Hybrid' => 6, 'LPG' => 2,
    ];

    /**
     * Masks for strings()->bothify(), result is uppercase - generic sample by default, language packs set real formats
     *
     * @var list<string>
     */
    protected const array LICENSE_PLATE_FORMATS = ['?? #####', '?? ####?', '??-###-??', '?## ???'];

    /** Characters allowed in VIN - no I, O, Q */
    private const string VIN_ALPHABET = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';

    /** Model year codes (10th VIN character) - no I, O, Q, U, Z, 0 */
    private const string VIN_YEAR_CODES = 'ABCDEFGHJKLMNPRSTVWXY123456789';

    /** @var array<string, int> */
    private const array VIN_VALUES = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'J' => 1, 'K' => 2, 'L' => 3,
        'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9, 'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8,
        'Z' => 9,
    ];

    private const array VIN_WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly StringsExtensionInterface $strings,
    ) {
    }

    /**
     * Manufacturer and matching model, e.g. "Toyota Corolla"
     */
    public function vehicle(): string
    {
        $manufacturer = $this->manufacturer();

        return $manufacturer . ' ' . $this->model($manufacturer);
    }

    /**
     * e.g. "Volkswagen"
     */
    public function manufacturer(): string
    {
        return $this->randomizer->randomKey(static::MANUFACTURERS);
    }

    /**
     * Model of given manufacturer, or of a random one, e.g. "Golf"
     */
    public function model(?string $manufacturer = null): string
    {
        return $this->randomizer->randomElement($this->manufacturerData($manufacturer ?? $this->manufacturer())['models']);
    }

    /**
     * Body type, e.g. "SUV"
     */
    public function type(): string
    {
        return $this->randomizer->randomElement(static::TYPES);
    }

    /**
     * Fuel, picked by popularity, e.g. "Diesel"
     */
    public function fuel(): string
    {
        return $this->randomizer->weightedElement(static::FUELS);
    }

    /**
     * Vehicle Identification Number (ISO 3779) with real manufacturer prefix and valid check digit (9th character),
     * e.g. "1HGCM82633A004352"
     */
    public function vin(?string $manufacturer = null): string
    {
        $wmi = $this->randomizer->randomElement($this->manufacturerData($manufacturer ?? $this->manufacturer())['wmi']);
        $descriptor = $this->randomizer->getBytesFromString(self::VIN_ALPHABET, 5);
        $year = $this->randomizer->getBytesFromString(self::VIN_YEAR_CODES, 1);
        $plant = $this->randomizer->getBytesFromString(self::VIN_ALPHABET, 1);
        $serial = $this->strings->numerify('######');

        $vin = $wmi . $descriptor . '0' . $year . $plant . $serial;
        $vin[8] = $this->vinCheckDigit($vin);

        return $vin;
    }

    /**
     * e.g. "KXP-4821"
     */
    public function licensePlate(): string
    {
        return strtoupper($this->strings->bothify($this->randomizer->randomElement(static::LICENSE_PLATE_FORMATS)));
    }

    /**
     * Check digit of 17 character VIN (9th character ignored): weighted sum mod 11, 10 is "X"
     */
    protected function vinCheckDigit(string $vin): string
    {
        $sum = 0;
        foreach (str_split($vin) as $position => $character) {
            $value = ctype_digit($character) ? (int) $character : self::VIN_VALUES[$character] ?? 0;
            $sum += $value * self::VIN_WEIGHTS[$position];
        }

        $remainder = $sum % 11;

        return $remainder === 10 ? 'X' : (string) $remainder;
    }

    /** @return array{wmi: list<string>, models: list<string>} */
    protected function manufacturerData(string $manufacturer): array
    {
        return static::MANUFACTURERS[$manufacturer] ?? throw new InvalidArgumentException(sprintf(
            'Unknown manufacturer "%s", use one of: %s',
            $manufacturer,
            implode(', ', array_keys(static::MANUFACTURERS)),
        ));
    }
}
