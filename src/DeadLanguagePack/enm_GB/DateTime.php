<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use DateTimeImmutable;
use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Middle English month and day names; long dates as medieval charters wrote them - the day (or the feast) and the
 * year of the reign: "Michelmasse, the iiij yeer of Kyng Charles the Thridde"
 */
class DateTime extends CoreDateTime
{
    use MedievalNumeralsTrait;

    /** @var list<string> */
    protected const array MONTHS = [
        'Januarie', 'Feverer', 'March', 'Aprill', 'May', 'Juyn', 'Juyl', 'August', 'Septembre', 'Octobre', 'Novembre',
        'Decembre',
    ];

    /** @var list<string> */
    protected const array MONTHS_IN_DATES = self::MONTHS;

    /** @var list<string> */
    protected const array DAYS = ['Monenday', 'Tewesday', 'Wednesday', 'Thursday', 'Friday', 'Saterday', 'Sonday'];

    /**
     * Feasts that dated documents and paid rents, by month-day
     *
     * @var array<string, string>
     */
    protected const array FEASTS = [
        '01-06' => 'Twelfthe Day', '02-02' => 'Candelmasse', '03-25' => 'Oure Lady Day',
        '04-23' => 'the Feeste of Seint George', '06-24' => 'Midsomer Day', '07-15' => 'Seint Swithunes Day',
        '08-01' => 'Lammasse', '09-29' => 'Michelmasse', '10-25' => 'Seint Crispines Day', '11-01' => 'Alle Halwen',
        '11-11' => 'Martynmasse', '12-25' => 'Cristemasse', '12-26' => 'Seint Stevenes Day', '12-28' => 'Childermasse',
    ];

    /**
     * Reigns as [accession date, monarch] - regnal years count from the accession
     *
     * @var list<array{string, string}>
     */
    protected const array REIGNS = [
        ['1837-06-20', 'Quene Victoria'], ['1901-01-22', 'Kyng Edward the Seventhe'],
        ['1910-05-06', 'Kyng George the Fifte'], ['1936-01-20', 'Kyng Edward the Eighte'],
        ['1936-12-11', 'Kyng George the Sixte'], ['1952-02-06', 'Quene Elizabeth the Seconde'],
        ['2022-09-08', 'Kyng Charles the Thridde'],
    ];

    /**
     * Feast or day of the month, and the year of the reign: "the xij day of Octobre, the iiij yeer of Kyng Charles
     * the Thridde", "Michelmasse, the v yeer of Kyng Charles the Thridde". Before 1837 the year of our Lord: "the
     * yeer of oure Lord mccclxxxvij". The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();
        $day = static::FEASTS[$date->format('m-d')]
            ?? sprintf('the %s day of %s', $this->medievalNumeral((int) $date->format('j')), static::MONTHS_IN_DATES[(int) $date->format('n') - 1]);

        return $day . ', ' . $this->year(new DateTimeImmutable($date->format('Y-m-d')));
    }

    /**
     * Year of the reign on given day, or the year of our Lord before the first reign of REIGNS
     */
    private function year(DateTimeImmutable $day): string
    {
        foreach (array_reverse(static::REIGNS) as [$accession, $monarch]) {
            $start = new DateTimeImmutable($accession);

            if ($day >= $start) {
                return sprintf('the %s yeer of %s', $this->medievalNumeral($start->diff($day)->y + 1), $monarch);
            }
        }

        return 'the yeer of oure Lord ' . $this->medievalNumeral((int) $day->format('Y'));
    }
}
