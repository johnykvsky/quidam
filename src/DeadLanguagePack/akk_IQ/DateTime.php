<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use DateTimeImmutable;
use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * The Babylonian calendar - lunar months from the first crescent, the year from the month after the spring equinox,
 * a second Addaru when twelve months fall short; years of the Seleucid era, as the last cuneiform tablets counted
 * them. Months follow the mean moon, so a date can be a day off the sky.
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'Nisannu', 'Ayyāru', 'Simānu', 'Duʾūzu', 'Abu', 'Ulūlu', 'Tašrītu', 'Araḫsamnu', 'Kislīmu', 'Ṭebētu', 'Šabāṭu',
        'Addaru',
    ];

    /**
     * Months in genitive - "ūmum 12 ša Nisanni"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'Nisanni', 'Ayyāri', 'Simāni', 'Duʾūzi', 'Abi', 'Ulūli', 'Tašrīti', 'Araḫsamni', 'Kislīmi', 'Ṭebēti', 'Šabāṭi',
        'Addari',
    ];

    /**
     * Days of the planets' gods - the Moon, Mars, Mercury, Jupiter, Venus, Saturn, the Sun. Babylon had no week; the
     * planetary week came later, from Babylonian astrology.
     *
     * @var list<string>
     */
    protected const array DAYS = ['ūm Sîn', 'ūm Nergal', 'ūm Nabû', 'ūm Marduk', 'ūm Ištar', 'ūm Ninurta', 'ūm Šamaš'];

    /** The second Addaru of years with thirteen months */
    protected const string LEAP_MONTH = 'Addaru arkû';

    /** @see self::LEAP_MONTH in genitive */
    protected const string LEAP_MONTH_IN_DATES = 'Addari arkî';

    /** A mean new moon - 6 January 2000, 18:14 UTC */
    private const int NEW_MOON = 947182440;

    /** Mean synodic month in seconds - 29.530588853 days */
    private const float SYNODIC_MONTH = 2551442.8769;

    /** Year 1 of the Seleucid era began in the spring of 311 BC */
    private const int SELEUCID_ERA = 311;

    /**
     * Babylonian month of given or random date, e.g. "Nisannu", "Addaru arkû" in years with thirteen months
     */
    public function monthName(?DateTimeInterface $date = null): string
    {
        $babylonian = $this->babylonian($date ?? $this->dateTime());

        return $babylonian['month'] < 12 ? static::MONTHS[$babylonian['month']] : static::LEAP_MONTH;
    }

    /**
     * Babylonian date of given or random date, the year of the Seleucid era: "ūmum 1 ša Nisanni, šanat 2337" (the
     * first day of the year 2026-27). The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $babylonian = $this->babylonian($date ?? $this->dateTime());
        $month = $babylonian['month'] < 12 ? static::MONTHS_IN_DATES[$babylonian['month']] : static::LEAP_MONTH_IN_DATES;

        return sprintf('ūmum %d ša %s, šanat %d', $babylonian['day'], $month, $babylonian['year'] + self::SELEUCID_ERA);
    }

    /**
     * @return array{month: int, day: int, year: int} month - 0 for Nisannu, 12 for the second Addaru; day of the month;
     *     year AD in which the Babylonian year began
     */
    private function babylonian(DateTimeInterface $date): array
    {
        $day = intdiv(new DateTimeImmutable($date->format('Y-m-d') . ' UTC')->getTimestamp(), 86400);
        $year = (int) $date->format('Y');
        $lunation = (int) floor(($day * 86400 - self::NEW_MOON) / self::SYNODIC_MONTH);

        while ($this->monthStart($lunation) > $day) {
            --$lunation;
        }

        while ($this->monthStart($lunation + 1) <= $day) {
            ++$lunation;
        }

        $nisannu = $this->nisannu($year);

        if ($lunation < $nisannu) {
            $nisannu = $this->nisannu(--$year);
        }

        return ['month' => $lunation - $nisannu, 'day' => $day - $this->monthStart($lunation) + 1, 'year' => $year];
    }

    /**
     * Lunation of Nisannu in $year - the first month beginning on or after the spring equinox (20 March)
     */
    private function nisannu(int $year): int
    {
        $equinox = intdiv(new DateTimeImmutable(sprintf('%04d-03-20 UTC', $year))->getTimestamp(), 86400);
        $lunation = (int) floor(($equinox * 86400 - self::NEW_MOON) / self::SYNODIC_MONTH) - 1;

        while ($this->monthStart($lunation) < $equinox) {
            ++$lunation;
        }

        return $lunation;
    }

    /**
     * First day of a lunation (days since 1970-01-01) - the crescent is first seen the evening after the new moon,
     * and the Babylonian day began at sunset
     */
    private function monthStart(int $lunation): int
    {
        return (int) floor((self::NEW_MOON + $lunation * self::SYNODIC_MONTH) / 86400) + 1;
    }
}
