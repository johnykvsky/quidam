<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\ang_GB;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Old English month names (as Bede records them, Winterfylleþ - "winter full moon" - for October) and day names;
 * long dates with the year as the Anglo-Saxon Chronicle writes it: "12 Winterfylleþ, anno mmxxvi"
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'Æfterra Gēola', 'Solmōnaþ', 'Hrēþmōnaþ', 'Ēastermōnaþ', 'Þrimilcemōnaþ', 'Ǣrra Līþa', 'Æfterra Līþa',
        'Wēodmōnaþ', 'Hāligmōnaþ', 'Winterfylleþ', 'Blōtmōnaþ', 'Ǣrra Gēola',
    ];

    /** @var list<string> */
    protected const array MONTHS_IN_DATES = self::MONTHS;

    /** @var list<string> */
    protected const array DAYS = ['Mōnandæg', 'Tīwesdæg', 'Wōdnesdæg', 'Þunresdæg', 'Frīgedæg', 'Sæterndæg', 'Sunnandæg'];

    /**
     * Day, month and the year in lowercase Roman numerals, as in the Chronicle: "12 Winterfylleþ, anno mmxxvi" -
     * the locale's long date format doesn't apply
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();
        $year = (int) $date->format('Y');

        return sprintf(
            '%d %s, anno %s',
            (int) $date->format('j'),
            static::MONTHS_IN_DATES[(int) $date->format('n') - 1],
            $year > 0 && $year < 4000 ? strtolower($this->roman($year)) : (string) $year,
        );
    }
}
