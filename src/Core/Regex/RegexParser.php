<?php

declare(strict_types = 1);

namespace Quidam\Core\Regex;

use Closure;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Parses a regular expression (subset of PCRE) into a generator of matching strings, used by Strings::regexify().
 *
 * Supported: literals (UTF-8), escapes, ".", character classes with ranges and negation, \d \w \s (and \D \W \S),
 * groups (capturing, non-capturing, named), alternation "|", quantifiers * + ? {n} {n,} {n,m} {,m} (lazy / possessive
 * suffixes are ignored), anchors ^ $ \A \z \Z (ignored - the whole string is generated).
 * Not supported (throw): backreferences, lookarounds, word boundaries, unicode properties, \Q..\E.
 *
 * "." and negated classes pick from printable ASCII (space to "~").
 *
 * @internal
 * @phpstan-type Range array{int, int}
 * @phpstan-type Node Closure(RandomizerInterface): string
 */
final class RegexParser
{
    /** Extra repetitions for unbounded quantifiers: * is 0-10, + is 1-11, {2,} is 2-12 */
    public const int UNBOUNDED_EXTRA = 10;

    private const int PRINTABLE_FIRST = 0x20;
    private const int PRINTABLE_LAST = 0x7E;

    /** @var list<string> */
    private array $chars;

    private int $position = 0;

    private function __construct(
        private readonly string $pattern,
    ) {
        if (!mb_check_encoding($pattern, 'UTF-8')) {
            throw new InvalidArgumentException('Regex pattern is not valid UTF-8');
        }

        $this->chars = mb_str_split($pattern);
    }

    /** @return Node */
    public static function parse(string $pattern): Closure
    {
        $parser = new self($pattern);
        $node = $parser->parseAlternation();

        if ($parser->position < count($parser->chars)) {
            throw $parser->error('unmatched ")"');
        }

        return $node;
    }

    /** @return Node */
    private function parseAlternation(): Closure
    {
        $branches = [$this->parseSequence()];

        while ($this->peek() === '|') {
            ++$this->position;
            $branches[] = $this->parseSequence();
        }

        if (count($branches) === 1) {
            return $branches[0];
        }

        return static fn (RandomizerInterface $randomizer): string => $randomizer->randomElement($branches)($randomizer);
    }

    /** @return Node */
    private function parseSequence(): Closure
    {
        $items = [];

        while (($char = $this->peek()) !== null && $char !== '|' && $char !== ')') {
            $items[] = $this->parseQuantifier($this->parseAtom());
        }

        if (count($items) === 1) {
            return $items[0];
        }

        return static function (RandomizerInterface $randomizer) use ($items): string {
            $result = '';
            foreach ($items as $item) {
                $result .= $item($randomizer);
            }

            return $result;
        };
    }

    /** @return Node */
    private function parseAtom(): Closure
    {
        $char = (string) $this->next();

        if ($char === '\\') {
            $escape = $this->parseEscape(false);

            return is_array($escape) ? $this->set($escape) : $this->literal($escape);
        }

        return match ($char) {
            '(' => $this->parseGroup(),
            '[' => $this->parseClass(),
            '.' => $this->set([[self::PRINTABLE_FIRST, self::PRINTABLE_LAST]]),
            '^', '$' => $this->literal(''),
            '*', '+', '?' => throw $this->error(sprintf('nothing to repeat before "%s"', $char)),
            default => $this->literal($char),
        };
    }

    /** @return Node */
    private function parseGroup(): Closure
    {
        if ($this->peek() === '?') {
            $this->skipGroupModifier();
        }

        $node = $this->parseAlternation();

        if ($this->next() !== ')') {
            throw $this->error('missing ")"');
        }

        return $node;
    }

    /**
     * Accepts (?:...), (?<name>...), (?P<name>...), (?'name'...) - all behave as plain groups
     */
    private function skipGroupModifier(): void
    {
        $rest = implode('', array_slice($this->chars, $this->position, 40));

        if (preg_match('/^\?(?::|P?<[A-Za-z_]\w*>|\'[A-Za-z_]\w*\')/', $rest, $match) !== 1) {
            throw $this->error('lookarounds, inline flags and other (?...) groups are not supported');
        }

        $this->position += mb_strlen($match[0]);
    }

    /**
     * @param Node $node
     * @return Node
     */
    private function parseQuantifier(Closure $node): Closure
    {
        $char = $this->peek();

        [$min, $max] = match ($char) {
            '*' => [0, self::UNBOUNDED_EXTRA],
            '+' => [1, 1 + self::UNBOUNDED_EXTRA],
            '?' => [0, 1],
            '{' => $this->parseBraces() ?? [null, null],
            default => [null, null],
        };

        if ($min === null || $max === null) {
            return $node;
        }

        if ($char !== '{') {
            ++$this->position;
        }

        // lazy or possessive suffix doesn't change what matches
        if ($this->peek() === '?' || $this->peek() === '+') {
            ++$this->position;
        }

        return static function (RandomizerInterface $randomizer) use ($node, $min, $max): string {
            $result = '';
            for ($i = $randomizer->getInt($min, $max); $i > 0; --$i) {
                $result .= $node($randomizer);
            }

            return $result;
        };
    }

    /**
     * {n}, {n,}, {n,m}, {,m} - anything else is a literal "{" (like in PCRE)
     *
     * @return array{int, int}|null
     */
    private function parseBraces(): ?array
    {
        $rest = implode('', array_slice($this->chars, $this->position, 30));

        if (preg_match('/^\{(?:(\d+)|(\d+),(\d*)|,(\d+))\}/', $rest, $match) !== 1) {
            return null;
        }

        $this->position += strlen($match[0]);
        [$min, $max] = match (true) {
            $match[1] !== '' => [(int) $match[1], (int) $match[1]],
            $match[2] !== '' => [(int) $match[2], $match[3] === '' ? (int) $match[2] + self::UNBOUNDED_EXTRA : (int) $match[3]],
            default => [0, (int) $match[4]],
        };

        if ($min > $max) {
            throw $this->error(sprintf('quantifier {%d,%d} has min higher than max', $min, $max));
        }

        return [$min, $max];
    }

    /** @return Node */
    private function parseClass(): Closure
    {
        $negated = $this->peek() === '^';
        if ($negated) {
            ++$this->position;
        }

        $ranges = [];
        $first = true;

        while (($char = $this->next()) !== ']' || $first) {
            $first = false;

            if ($char === null) {
                throw $this->error('missing "]"');
            }

            // POSIX [:alpha:], collating [.a.] and equivalence [=a=] classes - read as literals they'd give non-matching values
            $delimiter = $this->peek();
            if (
                $char === '[' && in_array($delimiter, [':', '.', '='], true)
                && str_contains(implode('', array_slice($this->chars, $this->position + 1)), $delimiter . ']')
            ) {
                throw $this->error(sprintf('POSIX classes like [%1$salpha%1$s] are not supported', $delimiter));
            }

            $literal = $char === '\\' ? $this->parseEscape(true) : $char;

            if (is_array($literal)) {
                array_push($ranges, ...$literal);
                continue;
            }

            $start = mb_ord($literal);

            // range a-z, but "-" at the end is a literal
            if ($this->peek() === '-' && ($this->chars[$this->position + 1] ?? ']') !== ']') {
                ++$this->position;
                $endChar = (string) $this->next();
                $endLiteral = $endChar === '\\' ? $this->parseEscape(true) : $endChar;

                if (is_array($endLiteral) || $endLiteral === '') {
                    throw $this->error('class shorthand cannot end a range');
                }

                $end = mb_ord($endLiteral);

                if ($start > $end) {
                    throw $this->error(sprintf('range "%s-%s" out of order', $literal, $endLiteral));
                }

                $ranges[] = [$start, $end];
                continue;
            }

            $ranges[] = [$start, $start];
        }

        $ranges = $negated ? $this->complement($ranges) : $this->normalize($ranges);

        if ($ranges === []) {
            throw $this->error('character class matches nothing printable');
        }

        return $this->set($ranges);
    }

    /**
     * Escaped character, ranges for shorthand classes (\d), empty string for anchors (\A)
     *
     * @return string|list<Range>
     */
    private function parseEscape(bool $inClass): string|array
    {
        $char = $this->next() ?? throw $this->error('pattern ends with "\\"');

        return match ($char) {
            'd' => [[0x30, 0x39]],
            'w' => [[0x30, 0x39], [0x41, 0x5A], [0x5F, 0x5F], [0x61, 0x7A]],
            's' => [[0x20, 0x20]],
            'D' => $this->complement([[0x30, 0x39]]),
            'W' => $this->complement([[0x30, 0x39], [0x41, 0x5A], [0x5F, 0x5F], [0x61, 0x7A]]),
            'S' => $this->complement([[0x20, 0x20], [0x09, 0x0D]]),
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'f' => "\f",
            'v' => "\v",
            'e' => "\e",
            '0' => "\0",
            'x' => $this->parseHex(),
            'b' => $inClass ? "\x08" : throw $this->error('word boundary \\b is not supported'),
            '1', '2', '3', '4', '5', '6', '7', '8', '9' => throw $this->error('backreferences are not supported'),
            'A', 'z', 'Z', 'G' => $inClass ? throw $this->error(sprintf('\\%s is not allowed in class', $char)) : '',
            default => ctype_alnum($char)
                ? throw $this->error(sprintf('escape \\%s is not supported', $char))
                : $char,
        };
    }

    /**
     * \xhh or \x{hhhh}
     */
    private function parseHex(): string
    {
        $rest = implode('', array_slice($this->chars, $this->position, 10));

        if (preg_match('/^(?:\{([0-9A-Fa-f]{1,6})\}|([0-9A-Fa-f]{1,2}))/', $rest, $match) !== 1) {
            throw $this->error('invalid \\x escape');
        }

        $this->position += strlen($match[0]);
        $code = (int) hexdec($match[1] !== '' ? $match[1] : $match[2]);

        if ($code > 0x10FFFF || ($code >= 0xD800 && $code <= 0xDFFF)) {
            throw $this->error('invalid code point in \\x escape');
        }

        return mb_chr($code);
    }

    /**
     * Printable ASCII characters not in given ranges
     *
     * @param list<Range> $ranges
     * @return list<Range>
     */
    private function complement(array $ranges): array
    {
        $result = [];
        $start = null;

        for ($code = self::PRINTABLE_FIRST; $code <= self::PRINTABLE_LAST + 1; ++$code) {
            $included = $code <= self::PRINTABLE_LAST && !$this->inRanges($code, $ranges);

            if ($included && $start === null) {
                $start = $code;
            } elseif (!$included && $start !== null) {
                $result[] = [$start, $code - 1];
                $start = null;
            }
        }

        return $result;
    }

    /** @param list<Range> $ranges */
    private function inRanges(int $code, array $ranges): bool
    {
        foreach ($ranges as [$from, $to]) {
            if ($code >= $from && $code <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorted, merged ranges - every character is equally likely, also when listed twice ([aab], [a-za-c])
     *
     * @param list<Range> $ranges
     * @return list<Range>
     */
    private function normalize(array $ranges): array
    {
        usort($ranges, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        $current = null;

        foreach ($ranges as [$from, $to]) {
            if ($current !== null && $from <= $current[1] + 1) {
                $current = [$current[0], max($current[1], $to)];
                continue;
            }

            if ($current !== null) {
                $merged[] = $current;
            }

            $current = [$from, $to];
        }

        if ($current !== null) {
            $merged[] = $current;
        }

        return $merged;
    }

    /**
     * Picks a character from ranges - every character equally likely
     *
     * @param list<Range> $ranges
     * @return Node
     */
    private function set(array $ranges): Closure
    {
        $total = 0;
        foreach ($ranges as [$from, $to]) {
            $total += $to - $from + 1;
        }

        return static function (RandomizerInterface $randomizer) use ($ranges, $total): string {
            $index = $randomizer->getInt(0, $total - 1);

            foreach ($ranges as [$from, $to]) {
                if ($index <= $to - $from) {
                    return (string) mb_chr($from + $index);
                }

                $index -= $to - $from + 1;
            }

            return '';
        };
    }

    /** @return Node */
    private function literal(string $literal): Closure
    {
        return static fn (): string => $literal;
    }

    private function peek(): ?string
    {
        return $this->chars[$this->position] ?? null;
    }

    private function next(): ?string
    {
        return $this->chars[$this->position++] ?? null;
    }

    private function error(string $message): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf('Invalid regex "%s": %s', $this->pattern, $message));
    }
}
