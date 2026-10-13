<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\got_IT;

/**
 * Wulfila's Gothic alphabet - letters for transliterated text, and the same letters as numbers: units a-þ, tens i-𐍁,
 * hundreds r-𐍊, written between dots (12 -> "·𐌹𐌱·")
 */
trait GothicAlphabetTrait
{
    /**
     * Gothic letter of a Latin one - "ai", "au", "ei" and "gg" (ng) are written with two letters in Gothic too;
     * c, v and y of loanwords as k, w and w
     *
     * @var array<string, string>
     */
    private const array GOTHIC_LETTERS = [
        'a' => '𐌰', 'b' => '𐌱', 'g' => '𐌲', 'd' => '𐌳', 'e' => '𐌴', 'q' => '𐌵', 'z' => '𐌶', 'h' => '𐌷', 'þ' => '𐌸',
        'i' => '𐌹', 'ï' => '𐌹', 'k' => '𐌺', 'c' => '𐌺', 'l' => '𐌻', 'm' => '𐌼', 'n' => '𐌽', 'j' => '𐌾', 'u' => '𐌿',
        'p' => '𐍀', 'r' => '𐍂', 's' => '𐍃', 't' => '𐍄', 'w' => '𐍅', 'v' => '𐍅', 'y' => '𐍅', 'f' => '𐍆', 'x' => '𐍇',
        'ƕ' => '𐍈', 'o' => '𐍉',
    ];

    /** @var list<string> letters for 1-9, 10-90 and 100-900 */
    private const array GOTHIC_UNITS = ['𐌰', '𐌱', '𐌲', '𐌳', '𐌴', '𐌵', '𐌶', '𐌷', '𐌸'];

    /** @var list<string> */
    private const array GOTHIC_TENS = ['𐌹', '𐌺', '𐌻', '𐌼', '𐌽', '𐌾', '𐌿', '𐍀', '𐍁'];

    /** @var list<string> */
    private const array GOTHIC_HUNDREDS = ['𐍂', '𐍃', '𐍄', '𐍅', '𐍆', '𐍇', '𐍈', '𐍉', '𐍊'];

    /**
     * Text in the Gothic alphabet, letters without a Gothic one left out: "Wulfila" -> "𐍅𐌿𐌻𐍆𐌹𐌻𐌰"
     */
    protected function gothicLetters(string $text): string
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map(
            static fn (string $word): string => implode('', array_map(static fn (string $letter): string => self::GOTHIC_LETTERS[$letter] ?? '', mb_str_split($word))),
            $words,
        ));
    }

    /**
     * Gothic numeral of 1-999 between dots, e.g. 12 -> "·𐌹𐌱·", 444 -> "·𐍅𐌼𐌳·"; other numbers as digits
     */
    protected function gothicNumeral(int $number): string
    {
        if ($number < 1 || $number > 999) {
            return (string) $number;
        }

        $letters = '';
        foreach ([[self::GOTHIC_HUNDREDS, intdiv($number, 100)], [self::GOTHIC_TENS, intdiv($number % 100, 10)], [self::GOTHIC_UNITS, $number % 10]] as [$digits, $digit]) {
            $letters .= $digit > 0 ? $digits[$digit - 1] : '';
        }

        return '·' . $letters . '·';
    }
}
