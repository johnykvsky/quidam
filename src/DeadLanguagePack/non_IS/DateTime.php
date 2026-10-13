<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\non_IS;

use DateTimeImmutable;
use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * The Old Icelandic calendar - two seasons of 30-day months. Summer starts on the Thursday between 19 and 25 April,
 * winter on the Saturday between 21 and 28 October; midsummer has four extra nights (aukanætr) and, about every
 * sixth year, a leap week (sumarauki), so the 364-day year keeps pace with the sun. Month and day names are Old Norse.
 */
class DateTime extends CoreDateTime
{
    /**
     * Months from the first day of summer
     *
     * @var list<string>
     */
    protected const array MONTHS = [
        'Harpa', 'Skerpla', 'Sólmánuðr', 'Heyannir', 'Tvímánuðr', 'Haustmánuðr', 'Gormánuðr', 'Ýlir', 'Mǫrsugr', 'Þorri',
        'Gói', 'Einmánuðr',
    ];

    /**
     * Months in genitive - "12. Gormánaðar"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'Hǫrpu', 'Skerplu', 'Sólmánaðar', 'Heyanna', 'Tvímánaðar', 'Haustmánaðar', 'Gormánaðar', 'Ýlis', 'Mǫrsugs',
        'Þorra', 'Góu', 'Einmánaðar',
    ];

    /** @var list<string> */
    protected const array DAYS = ['mánadagr', 'týsdagr', 'óðinsdagr', 'þórsdagr', 'frjádagr', 'laugardagr', 'sunnudagr'];

    /** Extra nights of every summer, after Sólmánuðr */
    private const int EXTRA_NIGHTS = 4;

    /**
     * Old Icelandic month of given or random date, e.g. "Gormánuðr"; "aukanætr" or "sumarauki" for the extra nights
     * and the leap week of midsummer
     */
    public function monthName(?DateTimeInterface $date = null): string
    {
        $norse = $this->norse($date ?? $this->dateTime());

        return $norse['month'] ?? $norse['extra'];
    }

    /**
     * Old Icelandic date of given or random date, month in genitive and the year AD: "1. Gormánaðar 2026"
     * (24 October 2026, first day of winter), "2. aukanátta 2026". The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $norse = $this->norse($date ?? $this->dateTime());
        $month = match ($norse['month']) {
            null => $norse['extra'] === 'sumarauki' ? 'sumarauka' : 'aukanátta',
            default => static::MONTHS_IN_DATES[(int) array_search($norse['month'], static::MONTHS, true)],
        };

        return sprintf('%d. %s %d', $norse['day'], $month, $norse['year']);
    }

    /**
     * @return array{month: ?string, extra: string, day: int, year: int} month - null in the extra nights and the leap
     *     week, extra - "aukanætr" or "sumarauki" then, day of the month (or of the extra days), year AD
     */
    private function norse(DateTimeInterface $date): array
    {
        $day = new DateTimeImmutable($date->format('Y-m-d') . ' UTC');
        $year = (int) $day->format('Y');
        $summer = $this->summerStart($year);
        $winter = $this->winterStart($year);

        if ($day < $summer) {
            // winter that began the autumn before
            return $this->winterDate($day, $this->winterStart($year - 1), $year);
        }

        if ($day >= $winter) {
            return $this->winterDate($day, $winter, $year);
        }

        $days = $this->daysBetween($summer, $day);
        $extra = $this->daysBetween($summer, $winter) - 180;

        return match (true) {
            $days < 90 => ['month' => static::MONTHS[intdiv($days, 30)], 'extra' => '', 'day' => $days % 30 + 1, 'year' => $year],
            $days < 90 + self::EXTRA_NIGHTS => ['month' => null, 'extra' => 'aukanætr', 'day' => $days - 89, 'year' => $year],
            $days < 90 + $extra => ['month' => null, 'extra' => 'sumarauki', 'day' => $days - 89 - self::EXTRA_NIGHTS, 'year' => $year],
            default => ['month' => static::MONTHS[3 + intdiv($days - 90 - $extra, 30)], 'extra' => '', 'day' => ($days - 90 - $extra) % 30 + 1, 'year' => $year],
        };
    }

    /**
     * Winter months from $winter - six of 30 days, the last takes what is left until summer
     *
     * @return array{month: string, extra: string, day: int, year: int}
     */
    private function winterDate(DateTimeImmutable $day, DateTimeImmutable $winter, int $year): array
    {
        $days = $this->daysBetween($winter, $day);
        $month = min(5, intdiv($days, 30));

        return ['month' => static::MONTHS[6 + $month], 'extra' => '', 'day' => $days - $month * 30 + 1, 'year' => $year];
    }

    /** First day of summer - the Thursday between 19 and 25 April */
    private function summerStart(int $year): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-04-18 UTC', $year))->modify('next thursday');
    }

    /**
     * First day of winter - 26 weeks and 2 days after summer began; a week later in years with sumarauki, which keeps
     * the next summer within 19-25 April (a Saturday between 21 and 28 October)
     */
    private function winterStart(int $year): DateTimeImmutable
    {
        $summer = $this->summerStart($year);
        $leapWeek = $this->daysBetween($summer, $this->summerStart($year + 1)) > 364;

        return $summer->modify($leapWeek ? '+191 days' : '+184 days');
    }

    private function daysBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return intdiv($to->getTimestamp() - $from->getTimestamp(), 86400);
    }
}
