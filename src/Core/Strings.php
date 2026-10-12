<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Core\Regex\RegexParser;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/** @phpstan-import-type Node from RegexParser */
class Strings implements StringsExtensionInterface
{
    /** @var array<string, Node> parsed regexify() patterns */
    private array $regexCache = [];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * Random string with length (in characters) between $min and $max, built from $pool characters (UTF-8 supported)
     */
    public function string(int $min = 3, int $max = 8, string $pool = self::ALPHA_POOL): string
    {
        if ($min < 1) {
            throw new InvalidArgumentException('$min should be at least 1');
        }

        if ($min > $max) {
            throw new InvalidArgumentException('$min cannot be higher than $max');
        }

        $length = $this->randomizer->getInt($min, $max);

        if (strlen($pool) === mb_strlen($pool)) {
            return $this->randomizer->getBytesFromString($pool, $length);
        }

        return implode('', $this->randomizer->randomElements(mb_str_split($pool), $length));
    }

    /**
     * Replaces every '#' with a random digit (0-9) and every '%' with a random non-zero digit (1-9)
     */
    public function numerify(string $string): string
    {
        $string = $this->replaceWildcard($string, '#', fn (): string => (string) $this->randomizer->getInt(0, 9));

        return $this->replaceWildcard($string, '%', fn (): string => (string) $this->randomizer->getInt(1, 9));
    }

    /**
     * Replaces every '?' with a random lowercase letter (a-z)
     */
    public function lexify(string $string): string
    {
        return $this->replaceWildcard($string, '?', $this->randomizer->randomLetter(...));
    }

    /**
     * Replaces '#' with digits, '?' with letters and '*' with either a digit or a letter
     */
    public function bothify(string $string): string
    {
        $string = $this->replaceWildcard($string, '*', fn (): string => $this->randomizer->getBool() ? '#' : '?');

        return $this->lexify($this->numerify($string));
    }

    /**
     * $length hexadecimal digits, e.g. "3fa9c2" - lowercase, or uppercase with $uppercase
     */
    public function hexadecimal(int $length = 6, bool $uppercase = false): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('hexadecimal() $length must be at least 1');
        }

        return $this->randomizer->getBytesFromString($uppercase ? '0123456789ABCDEF' : '0123456789abcdef', $length);
    }

    /**
     * Random string matching given regular expression (without delimiters), e.g. '[A-Z]{2}-\d{4}' → 'KX-4821'.
     *
     * Supports literals, ".", classes ([a-z0-9_], [^,]), \d \w \s, groups, alternation "|" and quantifiers
     * (* + ? {n} {n,} {n,m}). Unbounded quantifiers repeat at most 10 times more than their minimum. "." and negated
     * classes use printable ASCII. Anchors are ignored. Backreferences, lookarounds and \b throw.
     */
    public function regexify(string $pattern): string
    {
        return ($this->regexCache[$pattern] ??= RegexParser::parse($pattern))($this->randomizer);
    }

    /** @param callable(): string $replacement */
    protected function replaceWildcard(string $string, string $wildcard, callable $replacement): string
    {
        if (!str_contains($string, $wildcard)) {
            return $string;
        }

        $length = strlen($string);
        for ($i = 0; $i < $length; ++$i) {
            if ($string[$i] === $wildcard) {
                $string[$i] = $replacement();
            }
        }

        return $string;
    }
}
