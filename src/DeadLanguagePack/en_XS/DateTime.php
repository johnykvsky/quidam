<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Dates in Old Style, as England kept them until 1752 - the Julian calendar (13 days behind today's) with the year
 * beginning on Lady Day, 25 March: 12 October 2026 is "the nine and twentieth day of September, in the year of our
 * Lord 2026", 10 January 2026 "the eight and twentieth day of December, in the year of our Lord 2025".
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    private const array ORDINALS = [
        'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh',
        'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth',
        'twentieth',
    ];

    /** @var list<string> */
    private const array UNITS = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];

    /**
     * Month of given or random date in the Julian calendar - "September" for 12 October 2026
     */
    public function monthName(?DateTimeInterface $date = null): string
    {
        return static::MONTHS[$this->julian($date ?? $this->dateTime())[1] - 1];
    }

    /**
     * Old Style date of given or random date: "the nine and twentieth day of September, in the year of our Lord
     * 2026"; between 1 January and Lady Day both years, as in letters of the time - "in the year of our Lord 2025/6".
     * The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        [$year, $month, $day] = $this->julian($date ?? $this->dateTime());
        $before = $month < 3 || ($month === 3 && $day < 25);

        return sprintf(
            'the %s day of %s, in the year of our Lord %s',
            $this->ordinal($day),
            static::MONTHS_IN_DATES[$month - 1],
            $before ? $this->dualYear($year) : (string) $year,
        );
    }

    /**
     * Julian calendar date of a (Gregorian) date - through the Julian day number
     *
     * @return array{int, int, int} year, month, day
     */
    private function julian(DateTimeInterface $date): array
    {
        [$year, $month, $day] = array_map(intval(...), explode(' ', $date->format('Y n j')));

        $a = intdiv(14 - $month, 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        $jdn = $day + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;

        $c = $jdn + 32082;
        $d = intdiv(4 * $c + 3, 1461);
        $e = $c - intdiv(1461 * $d, 4);
        $m = intdiv(5 * $e + 2, 153);

        return [$d - 4800 + intdiv($m, 10), $m + 3 - 12 * intdiv($m, 10), $e - intdiv(153 * $m + 2, 5) + 1];
    }

    /**
     * "Old Style / New Style" year before Lady Day - only the digits that differ: 2025/6, 2029/30, 1699/1700
     */
    private function dualYear(int $year): string
    {
        $old = (string) ($year - 1);
        $new = (string) $year;
        $same = 0;

        while ($same < strlen($new) - 1 && $old[$same] === $new[$same]) {
            ++$same;
        }

        $digits = substr($new, $same);

        return $old . '/' . (strlen($digits) > 2 ? $new : $digits);
    }

    /**
     * 1-31 as an Elizabethan would say it: "first", "twelfth", "one and twentieth", "thirtieth"
     */
    private function ordinal(int $day): string
    {
        return match (true) {
            $day <= 20 => self::ORDINALS[$day - 1],
            $day === 30 => 'thirtieth',
            default => self::UNITS[$day % 10 - 1] . ' and ' . ($day > 30 ? 'thirtieth' : 'twentieth'),
        };
    }
}
