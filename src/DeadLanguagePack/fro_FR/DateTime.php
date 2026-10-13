<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Old French month and day names; long dates as charters wrote them - the day (or the feast) and the year of grace,
 * thousands in words and the rest in Roman numerals: "a la feste Saint Martin, l'an de grace mil CC et LX"
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'jenvier', 'fevrier', 'marz', 'avril', 'mai', 'juing', 'juignet', 'aoust', 'septembre', 'octobre', 'novembre',
        'decembre',
    ];

    /**
     * Months after "de" - elided before a vowel: "d'avril", "d'aoust", "d'octobre"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'de jenvier', 'de fevrier', 'de marz', 'd\'avril', 'de mai', 'de juing', 'de juignet', 'd\'aoust',
        'de septembre', 'd\'octobre', 'de novembre', 'de decembre',
    ];

    /** @var list<string> */
    protected const array DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'venredi', 'samedi', 'diemenche'];

    /**
     * Feasts that dated charters and paid rents, by month-day
     *
     * @var array<string, string>
     */
    protected const array FEASTS = [
        '01-06' => 'a la Tiephaine', '02-02' => 'a la Chandeleur', '06-11' => 'a la feste Saint Barnabé',
        '06-24' => 'a la feste Saint Jehan Baptiste', '07-22' => 'a la feste de la Magdelaine',
        '08-10' => 'a la feste Saint Lorent', '09-29' => 'a la feste Saint Michiel', '10-01' => 'a la feste Saint Remi',
        '10-09' => 'a la feste Saint Denis', '11-01' => 'a la Toz Sainz', '11-11' => 'a la feste Saint Martin',
        '12-06' => 'a la feste Saint Nicholas', '12-25' => 'a Noël',
    ];

    /** @var list<string> thousands of the year in words */
    private const array THOUSANDS = ['mil', 'deus mil', 'trois mil'];

    /**
     * Feast or day of the month ("le premier jor" for the first), and the year of grace: "le XII jor d'octobre, l'an de
     * grace deus mil et XXVI", "a la feste Saint Martin, l'an de grace mil CC et LX". The locale's long date format
     * doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();
        $number = (int) $date->format('j');
        $day = static::FEASTS[$date->format('m-d')]
            ?? sprintf('le %s jor %s', $number === 1 ? 'premier' : $this->roman($number), static::MONTHS_IN_DATES[(int) $date->format('n') - 1]);

        return $day . ', l\'an de grace ' . $this->year((int) $date->format('Y'));
    }

    /**
     * Year as charters wrote it - thousands in words, hundreds and the rest in Roman numerals joined by "et": 1260 ->
     * "mil CC et LX", 2026 -> "deus mil et XXVI", 842 -> "DCCC et XLII"; years before 1 and after 3999 as digits
     */
    private function year(int $year): string
    {
        if ($year < 1 || $year > 3999) {
            return (string) $year;
        }

        $hundreds = $this->roman($year % 1000 - $year % 100);
        $rest = $this->roman($year % 100);
        $parts = array_filter([$year >= 1000 ? self::THOUSANDS[intdiv($year, 1000) - 1] : '', $hundreds]);

        return implode(' ', $parts) . ($rest === '' ? '' : ($parts === [] ? '' : ' et ') . $rest);
    }
}
