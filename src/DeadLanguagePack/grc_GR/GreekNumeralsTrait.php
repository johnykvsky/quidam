<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

/**
 * Alphabetic (Milesian) numerals - letters for units, tens and hundreds, with the numeral sign: 7 -> "ζʹ", 701 -> "ψαʹ",
 * 2026 -> "͵βκϛʹ"
 */
trait GreekNumeralsTrait
{
    /** 1-9999, other numbers as digits */
    protected function greekNumeral(int $number): string
    {
        if ($number < 1 || $number > 9999) {
            return (string) $number;
        }

        $digits = [
            ['', 'α', 'β', 'γ', 'δ', 'ε', 'ϛ', 'ζ', 'η', 'θ'],
            ['', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π', 'ϟ'],
            ['', 'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω', 'ϡ'],
        ];
        $thousands = intdiv($number, 1000);

        return ($thousands > 0 ? '͵' . $digits[0][$thousands] : '')
            . $digits[2][intdiv($number, 100) % 10] . $digits[1][intdiv($number, 10) % 10] . $digits[0][$number % 10]
            . 'ʹ';
    }
}
