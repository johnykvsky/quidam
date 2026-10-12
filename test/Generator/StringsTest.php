<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Core\Strings;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class StringsTest extends GeneratorTestCase
{
    public function testMasks(): void
    {
        $text = $this->dummy->ext(StringsExtensionInterface::class);

        $this->assertAlwaysMatches('/^\d{3}-[1-9]{2}$/', static fn (): string => $text->numerify('###-%%'));
        $this->assertAlwaysMatches('/^[a-z]{3}-x$/', static fn (): string => $text->lexify('???-x'));
        $this->assertAlwaysMatches('/^[a-z]{2}\d{2}[a-z0-9]{2}$/', static fn (): string => $text->bothify('??##**'));
        self::assertSame('no wildcards', $text->bothify('no wildcards'));
    }

    public function testString(): void
    {
        $text = $this->dummy->ext(StringsExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z]{3,8}$/', static fn (): string => $text->string());
        $this->assertAlwaysMatches('/^[01]{4}$/', static fn (): string => $text->string(4, 4, '01'));
        $this->assertAlwaysMatches('/^[A-Za-z0-9]{10}$/', static fn (): string => $text->string(10, 10, Strings::ALPHA_NUM_POOL));
    }

    public function testMultibytePool(): void
    {
        $text = $this->dummy->ext(StringsExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $string = $text->string(5, 5, 'ąęłżź');
            self::assertTrue(mb_check_encoding($string, 'UTF-8'));
            self::assertSame(5, mb_strlen($string));
            self::assertMatchesRegularExpression('/^[ąęłżź]{5}$/u', $string);
        }
    }

    public function testInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(StringsExtensionInterface::class)->string(5, 4);
    }

    /** @return iterable<string, array{string}> */
    public static function regexPatterns(): iterable
    {
        yield 'class and count' => ['[A-Z]{2}-\d{4}'];
        yield 'email like' => ['[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}'];
        yield 'alternation and optional' => ['(foo|bar|baz)-qux?'];
        yield 'nested groups' => ['((ab|cd)(ef)?){2,3}'];
        yield 'non-capturing and named groups' => ['(?:x|y)(?<year>19|20)\d\d(?P<m>0[1-9]|1[0-2])'];
        yield 'negated class' => ['[^a-z0-9]{5}'];
        yield 'shorthands' => ['\w{3}\s\d\D\W\S'];
        yield 'dot' => ['a.{3}b'];
        yield 'unbounded' => ['x*y+z{2,}'];
        yield 'lazy and possessive suffixes' => ['a+?b*+c??'];
        yield 'escaped metacharacters' => ['\.\*\+\?\(\)\[\]\{\}\|\\\\\/\^\$'];
        yield 'class with escapes, dash and bracket' => ['[\d\-_.]{4}[]a]{2}[a-]'];
        yield 'anchors' => ['^\A[a-c]{2}\z$'];
        yield 'brace without quantifier is literal' => ['a{x}b{}c{,}'];
        yield 'hex and unicode' => ['\x41\x{1F600}[ąęł]{3}[\x{3B1}-\x{3C9}]'];
        yield 'empty alternative' => ['a(|b)c'];
        yield 'empty pattern' => [''];
    }

    #[DataProvider('regexPatterns')]
    public function testRegexify(string $pattern): void
    {
        $strings = $this->dummy->ext(StringsExtensionInterface::class);
        $regex = '#^(?:' . $pattern . ')$#u';

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $string = $strings->regexify($pattern);
            self::assertTrue(mb_check_encoding($string, 'UTF-8'));
            self::assertMatchesRegularExpression($regex, $string);
        }
    }

    public function testRegexifyQuantifierWithoutMin(): void
    {
        $strings = $this->dummy->ext(StringsExtensionInterface::class);

        // checked against {0,2} - PCRE2 before 10.43 treats {,2} as a literal
        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('#^a{0,2}b$#', $strings->regexify('a{,2}b'));
        }
    }

    public function testRegexifyLimitsUnboundedQuantifiers(): void
    {
        $strings = $this->dummy->ext(StringsExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertLessThanOrEqual(10, strlen($strings->regexify('a*')));
            self::assertLessThanOrEqual(15, strlen($strings->regexify('a{5,}')));
        }
    }

    public function testRegexifyCharactersAreEquallyLikely(): void
    {
        // 'a' listed three times - still one third
        $counts = array_count_values(str_split($this->dummy->ext(StringsExtensionInterface::class)->regexify('[aab-ca-a]{3000}')));
        ksort($counts);

        self::assertSame(['a', 'b', 'c'], array_keys($counts));
        foreach ($counts as $count) {
            self::assertGreaterThan(800, $count);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidRegexPatterns(): iterable
    {
        yield 'unclosed group' => ['(ab', 'missing ")"'];
        yield 'unmatched paren' => ['ab)', 'unmatched ")"'];
        yield 'unclosed class' => ['[ab', 'missing "]"'];
        yield 'nothing to repeat' => ['+a', 'nothing to repeat'];
        yield 'backreference' => ['(a)\1', 'backreferences are not supported'];
        yield 'lookahead' => ['a(?=b)', 'lookarounds'];
        yield 'word boundary' => ['\bword', 'word boundary'];
        yield 'unicode property' => ['\p{L}', 'escape \p is not supported'];
        yield 'POSIX class' => ['[[:alpha:]]{3}', 'POSIX classes like [:alpha:] are not supported'];
        yield 'POSIX collating class' => ['[[.a.]]', 'POSIX classes like [.alpha.] are not supported'];
        yield 'reversed range' => ['[z-a]', 'out of order'];
        yield 'reversed quantifier' => ['a{3,1}', 'min higher than max'];
        yield 'shorthand ends range' => ['[a-\d]', 'cannot end a range'];
        yield 'class matching nothing' => ['[^ -~]', 'matches nothing printable'];
        yield 'invalid code point' => ['\x{D800}', 'invalid code point'];
        yield 'trailing backslash' => ['a\\', 'ends with'];
    }

    #[DataProvider('invalidRegexPatterns')]
    public function testInvalidRegex(string $pattern, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->dummy->ext(StringsExtensionInterface::class)->regexify($pattern);
    }

    public function testHexadecimal(): void
    {
        $strings = $this->dummy->ext(StringsExtensionInterface::class);

        $this->assertAlwaysMatches('/^[0-9a-f]{6}$/', static fn (): string => $strings->hexadecimal());
        $this->assertAlwaysMatches('/^[0-9A-F]{12}$/', static fn (): string => $strings->hexadecimal(12, true));

        $this->expectException(InvalidArgumentException::class);
        $strings->hexadecimal(0);
    }
}
