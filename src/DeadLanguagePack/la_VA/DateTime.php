<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\la_VA;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Latin month and day names; long dates in the Roman calendar - days counted down to the Kalends, Nones or Ides,
 * year in Roman numerals: "a.d. V Id. Oct. MMXXVI"
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'Ianuarius', 'Februarius', 'Martius', 'Aprilis', 'Maius', 'Iunius', 'Iulius', 'Augustus', 'September',
        'October', 'November', 'December',
    ];

    /**
     * Abbreviated month adjectives of Roman dates - "Id. Mart."
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'Ian.', 'Feb.', 'Mart.', 'Apr.', 'Mai.', 'Iun.', 'Iul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.',
    ];

    /**
     * Planetary week, Monday first
     *
     * @var list<string>
     */
    protected const array DAYS = [
        'dies Lunae', 'dies Martis', 'dies Mercurii', 'dies Iovis', 'dies Veneris', 'dies Saturni', 'dies Solis',
    ];

    /**
     * Roman date of given or random date, year AD in Roman numerals: "Kal. Ian. MMXXVI" (1 January), "a.d. III Non.
     * Mart. MMXXVI" (5 March), "prid. Id. Mart. MMXXVI" (14 March), "a.d. bis VI Kal. Mart. MMXXIV" (leap day).
     * The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();
        [$day, $month, $year, $length] = array_map(intval(...), explode(' ', $date->format('j n Y t')));

        // Nones and Ides fall on the 7th and 15th in March, May, July and October, on the 5th and 13th otherwise
        $nones = in_array($month, [3, 5, 7, 10], true) ? 7 : 5;
        $ides = $nones + 8;

        $roman = match (true) {
            $day === 1 => 'Kal. ' . static::MONTHS_IN_DATES[$month - 1],
            $day <= $nones => $this->countdown($nones - $day + 1, 'Non.', $month),
            $day <= $ides => $this->countdown($ides - $day + 1, 'Id.', $month),
            default => $this->kalends($day, $month, $length),
        };

        return $roman . ' ' . ($year > 0 && $year < 4000 ? $this->roman($year) : (string) $year);
    }

    /**
     * Days after the Ides, counted to the Kalends of the next month. In leap years the 24th of February is doubled
     * ("bis sextum") - the day count of the 25th-29th is that of a common year's 24th-28th.
     */
    private function kalends(int $day, int $month, int $length): string
    {
        $next = $month % 12 + 1;

        if ($length === 29) {
            if ($day === 24) {
                return 'a.d. bis VI Kal. ' . static::MONTHS_IN_DATES[$next - 1];
            }

            if ($day > 24) {
                --$day;
            }

            $length = 28;
        }

        return $this->countdown($length - $day + 2, 'Kal.', $next);
    }

    /**
     * Inclusive countdown to a marker day: 1 - the day itself, 2 - "prid." (the day before), more - "a.d. V"
     */
    private function countdown(int $days, string $marker, int $month): string
    {
        $label = $marker . ' ' . static::MONTHS_IN_DATES[$month - 1];

        return match ($days) {
            1 => $label,
            2 => 'prid. ' . $label,
            default => 'a.d. ' . $this->roman($days) . ' ' . $label,
        };
    }
}
