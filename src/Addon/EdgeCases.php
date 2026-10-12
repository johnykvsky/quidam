<?php

declare(strict_types = 1);

namespace Quidam\Addon;

use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Strings that often break code - for negative tests, validation, escaping and encoding checks. Injection strings
 * are harmless probes (alert(1), {{7*7}}), they only show whether the input is escaped.
 *
 * Extend the class and override CASES to add own categories or strings.
 */
class EdgeCases implements EdgeCasesInterface
{
    /** @var array<string, list<string>> */
    protected const array CASES = [
        'blank' => [
            '',
            ' ',
            '   ',
            "\t",
            "\n",
            "\r\n",
            "\u{00A0}", // no-break space
            "\u{2003}", // em space
            "\u{3000}", // ideographic space
            ' leading and trailing spaces ',
        ],
        'reserved' => [
            'null', 'NULL', 'nil', 'None', 'undefined', 'true', 'false', 'TRUE', 'yes', 'no', 'N/A', '[object Object]',
            'CON', 'NUL', 'COM1', '.', '..', '~', '-', '0', '-0',
        ],
        'numeric' => [
            '-1', '00123', ' 42 ', '1e309', '-1e309', 'NaN', 'Infinity', '0x1F', '0b101', '1,000.00', '1.000,00', '½',
            '٣', '9223372036854775808', '-9223372036854775809', '0.1e-1000', '1/0',
        ],
        'unicode' => [
            'Ünïcödé',
            "e\u{0301}", // e + combining acute accent, looks like é
            'Straße', // uppercase is STRASSE - length changes
            'İstanbul ıi', // Turkish dotted / dotless i
            'ǅungla', // titlecase digraph
            'ﬁnancial', // ligature
            'ＡＢＣ１２３', // fullwidth
            '𝐁𝐨𝐥𝐝', // mathematical bold, 4-byte UTF-8
            '𠜎𠜱𠝹', // CJK extension B, 4-byte UTF-8
            '日本語のテキスト',
            '한국어 텍스트',
            'Ω≈ç√∫˜µ≤≥÷',
            'Zalgo: Z̵̡̢a̶̡͝l̷̨̛g̸̢̛o̵̧͝',
        ],
        'rtl' => [
            'مرحبا بالعالم',
            'שלום עולם',
            'abc שלום 123',
            "\u{202E}gnp.exe", // right-to-left override - shows as exe.png
            "left\u{200F}right", // right-to-left mark
        ],
        'invisible' => [
            "\u{200B}", // zero-width space
            "zero\u{200B}width",
            "\u{FEFF}bom", // byte order mark
            "soft\u{00AD}hyphen",
            "word\u{2060}joiner",
            "a\u{200D}b", // zero-width joiner
            "null\x00byte",
            "bell\x07",
            "\e[31mred\e[0m", // ANSI escape codes
        ],
        'emoji' => [
            '😀',
            '👍🏽', // skin tone modifier
            '👨‍👩‍👧‍👦', // ZWJ family - 7 code points, 1 visible character
            '🇵🇱', // flag - 2 regional indicators
            '❤️', // variation selector
            '1️⃣', // keycap
            'Text with 😀 emoji',
        ],
        'injection' => [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            '" OR ""="',
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '"><svg onload=alert(1)>',
            'javascript:alert(1)',
            '{{7*7}}',
            '${7*7}',
            '<%= 7*7 %>',
            '#{7*7}',
            '../../../etc/passwd',
            '..\\..\\windows\\win.ini',
            "header\r\nX-Injected: 1",
            '=1+1',
            '@SUM(1+1)',
            '+1+1',
            '*)(uid=*',
            '$(echo injected)',
            '`echo injected`',
            '%s%s%s%n',
            '{0}',
            '<!DOCTYPE x [<!ENTITY y "z">]><x>&y;</x>',
        ],
    ];

    /** Mix of 2, 3 and 4 byte UTF-8 characters for longString() */
    private const string MULTIBYTE_POOL = 'ąęłżźćńóśАБВГДЖЗИЛ日本語中文한국😀🎉👍';

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * Random edge case string, from given category or from all of them
     */
    public function string(?string $category = null): string
    {
        return $this->randomizer->randomElement($this->strings($category));
    }

    /**
     * All strings of given category (or all categories) - i.e. for data providers
     *
     * @return list<string>
     */
    public function strings(?string $category = null): array
    {
        if ($category === null) {
            return array_merge(...array_values(static::CASES));
        }

        if (!isset(static::CASES[$category])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown edge case category "%s". Available: %s',
                $category,
                implode(', ', $this->categories()),
            ));
        }

        return static::CASES[$category];
    }

    /** @return list<string> */
    public function categories(): array
    {
        return array_keys(static::CASES);
    }

    /**
     * String of exactly $length characters - ASCII letters, or multibyte UTF-8 ones (then byte length is 2-4 times
     * higher) - for length limits, column sizes, truncation
     */
    public function longString(int $length = 10000, bool $multibyte = false): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('$length must be at least 1');
        }

        if (!$multibyte) {
            return $this->randomizer->getBytesFromString('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length);
        }

        return implode('', $this->randomizer->randomElements(mb_str_split(self::MULTIBYTE_POOL), $length));
    }
}
