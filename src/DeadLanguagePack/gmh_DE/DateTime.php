<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\gmh_DE;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * The Old German months (Karl the Great gave them names, Middle High German kept them) and Middle High German day
 * names; long dates as charters wrote them - the day (or the feast) and the year of the Lord in words: "an sant
 * Martînes tage, dô man zalte von gotes gebürte zwei tûsent sehs und zweinzic jâr"
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'Hartmânôt', 'Hornunc', 'Lenzemânôt', 'Ôstermânôt', 'Meie', 'Brâchmânôt', 'Höumânôt', 'Ougest',
        'Herbestmânôt', 'Wînmânôt', 'Wintermânôt', 'Kristmânôt',
    ];

    /**
     * Months in genitive - "an dem 12. tage des Wînmânôdes"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'Hartmânôdes', 'Hornunges', 'Lenzemânôdes', 'Ôstermânôdes', 'Meien', 'Brâchmânôdes', 'Höumânôdes', 'Ougestes',
        'Herbestmânôdes', 'Wînmânôdes', 'Wintermânôdes', 'Kristmânôdes',
    ];

    /** @var list<string> */
    protected const array DAYS = ['mântac', 'zîstac', 'mittewoche', 'donerstac', 'vrîtac', 'sameztac', 'sunnentac'];

    /**
     * Feasts that dated charters, by month-day
     *
     * @var array<string, string>
     */
    protected const array FEASTS = [
        '01-06' => 'an dem obersten tage', '02-02' => 'an unser vrouwen tage ze der liehtmesse',
        '04-23' => 'an sant Georien tage', '05-01' => 'an sant Walpurge tage',
        '06-24' => 'an sant Johannes tage ze sunewenden', '07-25' => 'an sant Jâcobes tage',
        '08-10' => 'an sant Laurencien tage', '09-29' => 'an sant Michahêles tage', '11-01' => 'an aller heiligen tage',
        '11-11' => 'an sant Martînes tage', '11-25' => 'an sant Katherînen tage', '12-06' => 'an sant Niclaus tage',
        '12-25' => 'ze wîhennahten', '12-26' => 'an sant Stephanes tage',
    ];

    /** @var list<string> 1-19 */
    private const array ONES = [
        'ein', 'zwei', 'drî', 'vier', 'vünf', 'sehs', 'siben', 'aht', 'niun', 'zehen', 'einlif', 'zwelf', 'drîzehen',
        'vierzehen', 'vünfzehen', 'sehzehen', 'sibenzehen', 'ahtzehen', 'niunzehen',
    ];

    /** @var list<string> 20-90 */
    private const array TENS = ['zweinzic', 'drîzic', 'vierzic', 'vünfzic', 'sehzic', 'sibenzic', 'ahzic', 'niunzic'];

    /**
     * Feast or day of the month, and the year of the Lord in words: "an dem 12. tage des Wînmânôdes, dô man zalte
     * von gotes gebürte zwei tûsent sehs und zweinzic jâr". The locale's long date format doesn't apply.
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();
        $day = static::FEASTS[$date->format('m-d')]
            ?? sprintf('an dem %d. tage des %s', $date->format('j'), static::MONTHS_IN_DATES[(int) $date->format('n') - 1]);

        return sprintf('%s, dô man zalte von gotes gebürte %s jâr', $day, $this->words((int) $date->format('Y')));
    }

    /**
     * Number of 1-9999 in Middle High German words - units before tens, as in German: 2026 -> "zwei tûsent sehs und
     * zweinzic", 1260 -> "tûsent zwei hundert sehzic"; other numbers as digits
     */
    private function words(int $number): string
    {
        if ($number < 1 || $number > 9999) {
            return (string) $number;
        }

        $thousands = intdiv($number, 1000);
        $hundreds = intdiv($number % 1000, 100);
        $rest = $number % 100;

        return implode(' ', array_filter([
            match ($thousands) {
                0 => '',
                1 => 'tûsent',
                default => $this->one($thousands) . ' tûsent',
            },
            match ($hundreds) {
                0 => '',
                1 => 'hundert',
                default => $this->one($hundreds) . ' hundert',
            },
            match (true) {
                $rest === 0 => '',
                $rest < 20 => $this->one($rest),
                $rest % 10 === 0 => self::TENS[intdiv($rest, 10) - 2],
                default => $this->one($rest % 10) . ' und ' . self::TENS[intdiv($rest, 10) - 2],
            },
        ]));
    }

    /** Word of 1-19 */
    private function one(int $number): string
    {
        return self::ONES[$number - 1] ?? (string) $number;
    }
}
