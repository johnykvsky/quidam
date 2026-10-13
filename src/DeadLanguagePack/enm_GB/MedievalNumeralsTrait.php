<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

/**
 * Roman numerals as medieval scribes wrote them - lowercase, 4 as "iiij", a final "i" as "j": 1 -> "j",
 * 4 -> "iiij", 12 -> "xij", 2026 -> "mmxxvj"
 */
trait MedievalNumeralsTrait
{
    /** 1-3999, other numbers as digits */
    protected function medievalNumeral(int $number): string
    {
        if ($number < 1 || $number > 3999) {
            return (string) $number;
        }

        $roman = '';
        foreach (['m' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400, 'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40, 'x' => 10, 'ix' => 9, 'v' => 5, 'iiij' => 4, 'i' => 1] as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return str_ends_with($roman, 'i') ? substr($roman, 0, -1) . 'j' : $roman;
    }
}
