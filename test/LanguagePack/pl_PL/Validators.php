<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\pl_PL;

/**
 * Independent validators of Polish identifiers - written from the specifications, not from the generator code
 */
final class Validators
{
    public static function iban(string $iban): bool
    {
        $iban = str_replace(' ', '', $iban);
        $moved = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = (string) preg_replace_callback('/[A-Z]/', static fn (array $m): string => (string) (ord($m[0]) - 55), $moved);

        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    /**
     * Polish sort code (numer rozliczeniowy): weights 3, 9, 7, 1, 3, 9, 7, 1 - sum divisible by 10
     */
    public static function polishSortCode(string $code): bool
    {
        return preg_match('/^\d{8}$/', $code) === 1 && self::weighted($code, [3, 9, 7, 1, 3, 9, 7, 1]) % 10 === 0;
    }

    public static function pesel(string $pesel): bool
    {
        return preg_match('/^\d{11}$/', $pesel) === 1 && self::weighted($pesel, [1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 1]) % 10 === 0;
    }

    /**
     * Birth date (Y-m-d) and gender ('male' / 'female') encoded in PESEL
     *
     * @return array{string, string}
     */
    public static function peselData(string $pesel): array
    {
        $month = (int) substr($pesel, 2, 2);
        $century = [0 => 1900, 1 => 2000, 2 => 2100, 3 => 2200, 4 => 1800][intdiv($month, 20)];
        $date = sprintf('%04d-%02d-%s', $century + (int) substr($pesel, 0, 2), $month % 20, substr($pesel, 4, 2));

        return [$date, (int) $pesel[9] % 2 === 1 ? 'male' : 'female'];
    }

    public static function nip(string $nip): bool
    {
        $nip = str_replace('-', '', $nip);

        return preg_match('/^\d{10}$/', $nip) === 1 && self::weighted(substr($nip, 0, 9), [6, 5, 7, 2, 3, 4, 5, 6, 7]) % 11 === (int) $nip[9];
    }

    public static function regon(string $regon): bool
    {
        [$weights, $length] = strlen($regon) === 9 ? [[8, 9, 2, 3, 4, 5, 6, 7], 9] : [[2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8], 14];

        return preg_match('/^\d{' . $length . '}$/', $regon) === 1
            && self::weighted(substr($regon, 0, $length - 1), $weights) % 11 % 10 === (int) $regon[$length - 1];
    }

    /**
     * Polish ID card: AAA + check digit + 5 digits, weights 7, 3, 1, 9, 7, 3, 1, 7, 3 - sum divisible by 10
     */
    public static function polishIdCard(string $number): bool
    {
        if (preg_match('/^[A-NP-Z]{3}\d{6}$/', $number) !== 1 || str_contains($number, 'Q')) {
            return false;
        }

        $values = array_map(static fn (string $c): int => ctype_digit($c) ? (int) $c : ord($c) - 55, str_split($number));
        $sum = 0;
        foreach ([7, 3, 1, 9, 7, 3, 1, 7, 3] as $index => $weight) {
            $sum += $values[$index] * $weight;
        }

        return $sum % 10 === 0;
    }

    /**
     * Polish passport: 2 letters + 7 digits, weights 7, 3, 9, 1, 7, 3, 1, 7, 3 (A = 10), sum divisible by 10
     */
    public static function polishPassport(string $number): bool
    {
        if (preg_match('/^[A-Z]{2}\d{7}$/', $number) !== 1) {
            return false;
        }

        $sum = 0;
        foreach (str_split($number) as $index => $character) {
            $sum += (ctype_digit($character) ? (int) $character : ord($character) - 55) * [7, 3, 9, 1, 7, 3, 1, 7, 3][$index];
        }

        return $sum % 10 === 0;
    }

    /**
     * Land registry number "WA1M/00012345/6" - letter values as in kleczewsky/kw-cyfra (A = 1 ... in alphabet without
     * Q, V, X), weights 1, 3, 7, sum mod 10
     */
    public static function landRegistry(string $number): bool
    {
        if (preg_match('/^([A-Z0-9]{4})\/(\d{8})\/(\d)$/', $number, $m) !== 1) {
            return false;
        }

        $sum = 0;
        foreach (str_split($m[1] . $m[2]) as $index => $character) {
            $value = ctype_digit($character) ? (int) $character : (int) strpos('ABCDEFGHIJKLMNOPRSTUWYZ', $character) + 1;
            $sum += $value * [1, 3, 7][$index % 3];
        }

        return $sum % 10 === (int) $m[3];
    }

    /**
     * Polish doctor's licence number (PWZ) - first digit = sum of the others multiplied by 1-6, mod 11; not 0
     */
    public static function doctorLicence(string $number): bool
    {
        return preg_match('/^[1-9]\d{6}$/', $number) === 1 && self::weighted(substr($number, 1), [1, 2, 3, 4, 5, 6]) % 11 === (int) $number[0];
    }

    /** @param list<int> $weights */
    private static function weighted(string $digits, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $digits[$index] * $weight;
        }

        return $sum;
    }
}
