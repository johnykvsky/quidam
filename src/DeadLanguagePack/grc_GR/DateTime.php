<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * The Attic calendar - lunar months from the first new moon after the summer solstice, years counted in Olympiads:
 * 12 October 2026 is "αʹ Pyanepsionos, Ol. ψαʹ, etos βʹ" (1st of Pyanepsion, 2nd year of the 701st Olympiad).
 * Months follow the mean new moon (+1 day for the first crescent) - within a day or two of what the archons
 * proclaimed, who moved the calendar when it suited them anyway.
 */
class DateTime extends CoreDateTime
{
    use GreekNumeralsTrait;

    /** @var list<string> */
    protected const array MONTHS = [
        'Hekatombaion', 'Metageitnion', 'Boedromion', 'Pyanepsion', 'Maimakterion', 'Poseideon', 'Gamelion',
        'Anthesterion', 'Elaphebolion', 'Mounichion', 'Thargelion', 'Skirophorion',
    ];

    /**
     * Months in genitive - "of Pyanepsion"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'Hekatombaionos', 'Metageitnionos', 'Boedromionos', 'Pyanepsionos', 'Maimakterionos', 'Poseideonos',
        'Gamelionos', 'Anthesterionos', 'Elaphebolionos', 'Mounichionos', 'Thargelionos', 'Skirophorionos',
    ];

    /**
     * Planetary week of the Hellenistic age, Monday first
     *
     * @var list<string>
     */
    protected const array DAYS = [
        'hemera Selenes', 'hemera Areos', 'hemera Hermou', 'hemera Dios', 'hemera Aphrodites', 'hemera Kronou',
        'hemera Heliou',
    ];

    /** Mean length of a lunar month in seconds (29.530588853 days) */
    private const float SYNODIC_MONTH = 2551442.877;

    /** A new moon: 2000-01-06 18:14 UTC */
    private const int NEW_MOON = 947182440;

    /** Index of the month repeated in 13-month years - Poseideon */
    private const int INTERCALATED = 5;

    /**
     * Attic month of given or random date, e.g. "Pyanepsion"; "Poseideon deuteros" - the second Poseideon of
     * 13-month years
     */
    public function monthName(?DateTimeInterface $date = null): string
    {
        $attic = $this->attic($date ?? $this->dateTime());

        return $attic['second'] ? static::MONTHS[self::INTERCALATED] . ' deuteros' : static::MONTHS[$attic['month']];
    }

    /**
     * Attic date of given or random date, day and Olympiad in Greek numerals: "αʹ Pyanepsionos, Ol. ψαʹ, etos βʹ".
     * The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $attic = $this->attic($date ?? $this->dateTime());
        $month = $attic['second'] ? static::MONTHS_IN_DATES[self::INTERCALATED] . ' deuterou' : static::MONTHS_IN_DATES[$attic['month']];
        $date = $this->greekNumeral($attic['day']) . ' ' . $month;

        // the first Olympiad began in the summer of 776 BC - Attic years start in summer too
        $years = $attic['year'] + 775;

        return $years < 0
            ? $date
            : sprintf('%s, Ol. %s, etos %s', $date, $this->greekNumeral(intdiv($years, 4) + 1), $this->greekNumeral($years % 4 + 1));
    }

    /**
     * @return array{month: int, second: bool, day: int, year: int} month 0-11 (Hekatombaion first), second - the
     *     intercalated Poseideon, day 1-30, year - the year AD in which the Attic year began
     */
    private function attic(DateTimeInterface $date): array
    {
        [$year, $monthOfYear, $dayOfMonth] = array_map(intval(...), explode(' ', $date->format('Y n j')));
        $noon = (float) gmmktime(12, 0, 0, $monthOfYear, $dayOfMonth, $year);

        $monthStart = $this->monthStart($noon);
        $yearStart = $this->yearStart($year);

        if ($monthStart < $yearStart) {
            $yearStart = $this->yearStart(--$year);
        }

        $month = (int) round(($monthStart - $yearStart) / self::SYNODIC_MONTH);
        $leap = round(($this->yearStart($year + 1) - $yearStart) / self::SYNODIC_MONTH) > 12;

        return [
            'month' => $leap && $month > self::INTERCALATED ? $month - 1 : $month,
            'second' => $leap && $month === self::INTERCALATED + 1,
            'day' => (int) floor(($noon - $monthStart) / 86400) + 1,
            'year' => $year,
        ];
    }

    /**
     * Start of the month containing $time - a day after the new moon, when the crescent shows (noumenia)
     */
    private function monthStart(float $time): float
    {
        $first = self::NEW_MOON + 86400;

        return $first + floor(($time - $first) / self::SYNODIC_MONTH) * self::SYNODIC_MONTH;
    }

    /**
     * Start of the Attic year beginning in $year AD - the first month after the summer solstice (21 June)
     */
    private function yearStart(int $year): float
    {
        $first = self::NEW_MOON + 86400;
        $solstice = (float) gmmktime(0, 0, 0, 6, 21, $year);

        return $first + ceil(($solstice - $first) / self::SYNODIC_MONTH) * self::SYNODIC_MONTH;
    }
}
