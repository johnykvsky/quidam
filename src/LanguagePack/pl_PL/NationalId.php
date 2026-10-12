<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use DateTimeInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Polish identifiers with valid check digits: PESEL, NIP, REGON, ID card and passport numbers, land registry (KW) and
 * doctor's licence (PWZ) numbers; KRS and EU VAT numbers
 */
class NationalId implements NationalIdInterface
{
    /** Letters used in ID card series (no O and Q) */
    private const string ID_CARD_LETTERS = 'ABCDEFGHIJKLMNPRSTUVWXYZ';

    /** Letters of the second character of biometric passport series ("EA", "ES"...) */
    private const string PASSPORT_SERIES_LETTERS = 'ABCDEFGHIJKLMNPRSTUVWXYZ';

    /** Character values in land registry numbers: digits, then X = 10, A = 11 ... Z = 33 */
    private const string LAND_REGISTRY_CHARACTERS = '0123456789XABCDEFGHIJKLMNOPRSTUWYZ';

    /**
     * Land registry court department codes (kod wydziału) - annex 1 to the regulation of the Minister of Justice of
     * 15 February 2016, as amended by Dz.U. 2024 poz. 939. Without GL1X (Żory): the value of X in the check digit
     * couldn't be confirmed
     *
     * @var list<string>
     */
    protected const array LAND_REGISTRY_COURTS = [
        'BB1B', 'BB1C', 'BB1Z', 'BI1B', 'BI1P', 'BI1S', 'BI2P', 'BI3P', 'BY1B', 'BY1I', 'BY1M', 'BY1N', 'BY1S', 'BY1T',
        'BY1U', 'BY1Z', 'BY2T', 'CZ1C', 'CZ1L', 'CZ1M', 'CZ1Z', 'EL1B', 'EL1D', 'EL1E', 'EL1I', 'EL1N', 'EL1O', 'EL2O',
        'GD1A', 'GD1E', 'GD1G', 'GD1I', 'GD1M', 'GD1R', 'GD1S', 'GD1T', 'GD1W', 'GD1Y', 'GD2I', 'GD2M', 'GL1G', 'GL1J',
        'GL1R', 'GL1S', 'GL1T', 'GL1W', 'GL1Y', 'GL1Z', 'GW1G', 'GW1K', 'GW1M', 'GW1S', 'GW1U', 'JG1B', 'JG1J', 'JG1K',
        'JG1L', 'JG1S', 'JG1Z', 'KA1B', 'KA1C', 'KA1D', 'KA1I', 'KA1J', 'KA1K', 'KA1L', 'KA1M', 'KA1P', 'KA1S', 'KA1T',
        'KA1Y', 'KI1A', 'KI1B', 'KI1H', 'KI1I', 'KI1J', 'KI1K', 'KI1L', 'KI1O', 'KI1P', 'KI1R', 'KI1S', 'KI1T', 'KI1W',
        'KN1K', 'KN1N', 'KN1S', 'KN1T', 'KO1B', 'KO1D', 'KO1E', 'KO1I', 'KO1K', 'KO1L', 'KO1W', 'KO2B', 'KR1B', 'KR1C',
        'KR1E', 'KR1H', 'KR1I', 'KR1K', 'KR1M', 'KR1O', 'KR1P', 'KR1S', 'KR1W', 'KR1Y', 'KR2E', 'KR2I', 'KR2K', 'KR2P',
        'KR2Y', 'KR3I', 'KS1B', 'KS1E', 'KS1J', 'KS1K', 'KS1S', 'KS2E', 'KZ1A', 'KZ1E', 'KZ1J', 'KZ1O', 'KZ1P', 'KZ1R',
        'KZ1W', 'LD1B', 'LD1G', 'LD1H', 'LD1K', 'LD1M', 'LD1O', 'LD1P', 'LD1R', 'LD1Y', 'LE1G', 'LE1J', 'LE1L', 'LE1U',
        'LE1Z', 'LM1G', 'LM1L', 'LM1W', 'LM1Z', 'LU1A', 'LU1B', 'LU1C', 'LU1I', 'LU1K', 'LU1O', 'LU1P', 'LU1R', 'LU1S',
        'LU1U', 'LU1W', 'LU1Y', 'NS1G', 'NS1L', 'NS1M', 'NS1S', 'NS1T', 'NS1Z', 'NS2L', 'OL1B', 'OL1C', 'OL1E', 'OL1G',
        'OL1K', 'OL1L', 'OL1M', 'OL1N', 'OL1O', 'OL1P', 'OL1S', 'OL1Y', 'OL2G', 'OP1B', 'OP1G', 'OP1K', 'OP1L', 'OP1N',
        'OP1O', 'OP1P', 'OP1S', 'OP1U', 'OS1M', 'OS1O', 'OS1P', 'OS1U', 'OS1W', 'PL1C', 'PL1E', 'PL1G', 'PL1L', 'PL1M',
        'PL1O', 'PL1P', 'PL1Z', 'PL2M', 'PO1A', 'PO1B', 'PO1D', 'PO1E', 'PO1F', 'PO1G', 'PO1H', 'PO1I', 'PO1K', 'PO1L',
        'PO1M', 'PO1N', 'PO1O', 'PO1P', 'PO1R', 'PO1S', 'PO1T', 'PO1Y', 'PO1Z', 'PO2A', 'PO2H', 'PO2P', 'PO2T', 'PR1J',
        'PR1L', 'PR1P', 'PR1R', 'PR2R', 'PT1B', 'PT1O', 'PT1P', 'PT1R', 'PT1T', 'RA1G', 'RA1K', 'RA1L', 'RA1P', 'RA1R',
        'RA1S', 'RA1Z', 'RA2G', 'RZ1A', 'RZ1D', 'RZ1E', 'RZ1R', 'RZ1S', 'RZ1Z', 'SI1G', 'SI1M', 'SI1P', 'SI1S', 'SI1W',
        'SI2S', 'SL1B', 'SL1C', 'SL1L', 'SL1M', 'SL1S', 'SL1Z', 'SO1C', 'SR1L', 'SR1S', 'SR1W', 'SR1Z', 'SR2L', 'SR2W',
        'SU1A', 'SU1N', 'SU1S', 'SW1D', 'SW1K', 'SW1S', 'SW1W', 'SW1Z', 'SZ1C', 'SZ1G', 'SZ1K', 'SZ1L', 'SZ1M', 'SZ1O',
        'SZ1S', 'SZ1T', 'SZ1W', 'SZ1Y', 'SZ2S', 'SZ2T', 'TB1K', 'TB1M', 'TB1N', 'TB1S', 'TB1T', 'TO1B', 'TO1C', 'TO1G',
        'TO1T', 'TO1U', 'TO1W', 'TR1B', 'TR1D', 'TR1O', 'TR1T', 'TR2T', 'WA1G', 'WA1I', 'WA1L', 'WA1M', 'WA1N', 'WA1O',
        'WA1P', 'WA1W', 'WA2M', 'WA3M', 'WA4M', 'WA5M', 'WA6M', 'WL1A', 'WL1L', 'WL1R', 'WL1W', 'WL1Y', 'WR1E', 'WR1K',
        'WR1L', 'WR1M', 'WR1O', 'WR1S', 'WR1T', 'WR1W', 'ZA1B', 'ZA1H', 'ZA1J', 'ZA1K', 'ZA1T', 'ZA1Z', 'ZG1E', 'ZG1G',
        'ZG1K', 'ZG1N', 'ZG1R', 'ZG1S', 'ZG1W', 'ZG2K', 'ZG2S',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly DateTimeExtensionInterface $dateTime,
        protected readonly StringsExtensionInterface $strings,
    ) {
    }

    /**
     * PESEL - 11 digits encoding birth date and gender, e.g. "90010112345". Pass birth date and gender of a person
     * to match it (i.e. from profile()->person()), random when null.
     */
    public function pesel(?DateTimeInterface $birthDate = null, ?Gender $gender = null): string
    {
        $birthDate ??= $this->dateTime->dateOfBirth(0, 99);
        $year = (int) $birthDate->format('Y');

        if ($year < 1800 || $year > 2299) {
            throw new InvalidArgumentException(sprintf('PESEL supports birth years 1800-2299, %d given', $year));
        }

        $gender ??= $this->randomizer->getBool() ? Gender::Male : Gender::Female;

        // century is encoded in the month: 1800s +80, 1900s +0, 2000s +20, 2100s +40, 2200s +60
        $month = (int) $birthDate->format('n') + [18 => 80, 19 => 0, 20 => 20, 21 => 40, 22 => 60][intdiv($year, 100)];
        $serial = $this->strings->numerify('###');
        // gender digit: even for women, odd for men
        $genderDigit = 2 * $this->randomizer->getInt(0, 4) + ($gender === Gender::Male ? 1 : 0);

        $number = sprintf('%02d%02d%02d%s%d', $year % 100, $month, (int) $birthDate->format('j'), $serial, $genderDigit);

        return $number . $this->checkDigit($number, [1, 3, 7, 9, 1, 3, 7, 9, 1, 3]);
    }

    /**
     * NIP (tax identification number) - 10 digits, e.g. "5261040828" or formatted "526-104-08-28"
     */
    public function nip(bool $formatted = false): string
    {
        do {
            $number = $this->strings->numerify('%%%######');
            $check = $this->weightedSum($number, [6, 5, 7, 2, 3, 4, 5, 6, 7]) % 11;
        } while ($check === 10); // no valid check digit - such numbers are never issued

        $nip = $number . $check;

        return $formatted ? sprintf('%s-%s-%s-%s', substr($nip, 0, 3), substr($nip, 3, 3), substr($nip, 6, 2), substr($nip, 8, 2)) : $nip;
    }

    /**
     * REGON (business register number) - 9 digits, e.g. "012345675"
     */
    public function regon(): string
    {
        // first two digits - voivodeship code, odd number 01-97
        $number = sprintf('%02d', 2 * $this->randomizer->getInt(0, 48) + 1) . $this->strings->numerify('######');

        return $number . $this->weightedSum($number, [8, 9, 2, 3, 4, 5, 6, 7]) % 11 % 10;
    }

    /**
     * REGON of a local unit - 14 digits (9-digit REGON, 4-digit unit, check digit)
     */
    public function regonLocal(): string
    {
        $number = $this->regon() . $this->strings->numerify('####');

        return $number . $this->weightedSum($number, [2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8]) % 11 % 10;
    }

    /**
     * ID card (dowód osobisty) number - 3 letters, check digit, 5 digits, e.g. "ABA300000"
     */
    public function idCardNumber(): string
    {
        $letters = $this->randomizer->getBytesFromString(self::ID_CARD_LETTERS, 3);
        $digits = $this->strings->numerify('#####');
        $values = [...array_map(static fn (string $letter): int => ord($letter) - 55, str_split($letters)), ...array_map(intval(...), str_split($digits))];
        $check = 0;

        foreach ([7, 3, 1, 7, 3, 1, 7, 3] as $index => $weight) {
            $check += $values[$index] * $weight;
        }

        return $letters . ($check % 10) . $digits;
    }

    /**
     * Passport number - series "E" + letter (biometric passports), check digit, 6 digits, e.g. "ES5182736". Weights
     * 7, 3, 9, 1, 7, 3, 1, 7, 3 (letters A = 10 ... Z = 35), sum divisible by 10
     */
    public function passportNumber(): string
    {
        $series = 'E' . $this->randomizer->getBytesFromString(self::PASSPORT_SERIES_LETTERS, 1);
        $digits = $this->strings->numerify('######');
        $values = [...array_map(static fn (string $letter): int => ord($letter) - 55, str_split($series)), ...array_map(intval(...), str_split($digits))];
        $sum = 0;

        foreach ([7, 3, 1, 7, 3, 1, 7, 3] as $index => $weight) {
            $sum += $values[$index] * $weight;
        }

        // the check digit has weight 9 (= -1 mod 10), so it equals the sum of the others mod 10
        return $series . ($sum % 10) . $digits;
    }

    /**
     * Land registry (księga wieczysta) number - real court code, 8 digits, check digit, e.g. "WA1M/00012345/6"
     */
    public function landRegistryNumber(): string
    {
        $court = $this->randomizer->randomElement(static::LAND_REGISTRY_COURTS);
        $number = sprintf('%08d', $this->randomizer->getInt(1, 999999));
        $sum = 0;

        foreach (str_split($court . $number) as $index => $character) {
            $sum += (int) strpos(self::LAND_REGISTRY_CHARACTERS, $character) * [1, 3, 7][$index % 3];
        }

        return $court . '/' . $number . '/' . ($sum % 10);
    }

    /**
     * Doctor's licence number (numer prawa wykonywania zawodu, PWZ) - 7 digits, the first is a check digit: sum of
     * the others multiplied by 1-6, mod 11; never 0, e.g. "5425740"
     */
    public function doctorLicenceNumber(): string
    {
        do {
            $digits = $this->strings->numerify('######');
            $check = $this->weightedSum($digits, [1, 2, 3, 4, 5, 6]) % 11;
        } while ($check === 0 || $check === 10);

        return $check . $digits;
    }

    /**
     * National Court Register (KRS) number - 10 digits with leading zeros, no check digit, e.g. "0000123456"
     */
    public function krs(): string
    {
        return sprintf('%010d', $this->randomizer->getInt(1, 1100000));
    }

    /**
     * EU VAT number - "PL" and NIP, e.g. "PL5261040828"
     */
    public function euVatNumber(): string
    {
        return 'PL' . $this->nip();
    }

    /**
     * (10 - weighted sum mod 10) mod 10
     *
     * @param list<int> $weights
     */
    protected function checkDigit(string $number, array $weights): int
    {
        return (10 - $this->weightedSum($number, $weights) % 10) % 10;
    }

    /** @param list<int> $weights */
    protected function weightedSum(string $number, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $number[$index] * $weight;
        }

        return $sum;
    }
}
