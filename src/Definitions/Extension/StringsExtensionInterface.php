<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface StringsExtensionInterface extends ExtensionInterface
{
    public const string ALPHA_POOL = 'abcdefghijklmnopqrstuvwxyz';

    public const string ALPHA_CASE_POOL = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public const string ALPHA_NUM_POOL = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Random string with length (in characters) between $min and $max, built from $pool characters (UTF-8 supported)
     */
    public function string(int $min = 3, int $max = 8, string $pool = self::ALPHA_POOL): string;

    /**
     * Replaces every '#' with a random digit (0-9) and every '%' with a random non-zero digit (1-9)
     */
    public function numerify(string $string): string;

    /**
     * Replaces every '?' with a random lowercase letter (a-z)
     */
    public function lexify(string $string): string;

    /**
     * Replaces '#' with digits, '?' with letters and '*' with either a digit or a letter
     */
    public function bothify(string $string): string;

    /**
     * $length hexadecimal digits, e.g. "3fa9c2" - lowercase, or uppercase with $uppercase
     */
    public function hexadecimal(int $length = 6, bool $uppercase = false): string;

    /**
     * Random string matching given regular expression (without delimiters), e.g. '[A-Z]{2}-\d{4}' → 'KX-4821'.
     *
     * Supports literals, ".", classes ([a-z0-9_], [^,]), \d \w \s, groups, alternation "|" and quantifiers
     * (* + ? {n} {n,} {n,m}). Unbounded quantifiers repeat at most 10 times more than their minimum. "." and negated
     * classes use printable ASCII. Anchors are ignored. Backreferences, lookarounds and \b throw.
     */
    public function regexify(string $pattern): string;
}
