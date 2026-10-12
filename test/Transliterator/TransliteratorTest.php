<?php

declare(strict_types = 1);

namespace Quidam\Test\Transliterator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Transliterator\TransliteratorInterface;
use Quidam\Quidam;

final class TransliteratorTest extends TestCase
{
    private TransliteratorInterface $transliterator;

    protected function setUp(): void
    {
        $this->transliterator = Quidam::create()->ext(TransliteratorInterface::class);
    }

    /** @return iterable<string, array{string, string}> */
    public static function texts(): iterable
    {
        yield 'ascii untouched' => ['Jean-Luc Picard, 42!', 'Jean-Luc Picard, 42!'];
        yield 'polish' => ['Łukasz Żółć', 'Lukasz Zolc'];
        yield 'german' => ['Straße Müller', 'Strasse Muller'];
        yield 'nordic' => ['Øresund Ærø Þór', 'Oresund AEro THor'];
        yield 'slovak' => ['Ľubomír Ĺ', 'Lubomir L'];
        yield 'vietnamese' => ['Đắk Lắk Nguyễn', 'Dak Lak Nguyen'];
        yield 'russian' => ['Щука Юлия Ёлкин Жуков', 'Shchuka Yuliya Yolkin Zhukov'];
        yield 'russian lowercase' => ['щука', 'shchuka'];
        yield 'serbian' => ['Ђорђе Љубица', 'Djordje Ljubitsa'];
        yield 'greek' => ['Ελλάδα Θεσσαλονίκη Ουρανός', 'Ellada Thessaloniki Ouranos'];
        yield 'georgian' => ['თბილისი', 'tbilisi'];
        yield 'armenian' => ['Երևան', 'Erevan'];
        yield 'punctuation' => ['«quoted» – “text”…', '"quoted" - "text"...'];
        yield 'ligatures and fractions' => ['ﬁnal ½', 'final 1/2'];
        yield 'unknown removed' => ['北京 Tokyo', ' Tokyo'];
    }

    #[DataProvider('texts')]
    public function testTransliterate(string $text, string $expected): void
    {
        self::assertSame($expected, $this->transliterator->transliterate($text));
    }

    public function testSlug(): void
    {
        self::assertSame('zolta-lodz', $this->transliterator->slug('Żółta Łódź!'));
        self::assertSame('hello_world', $this->transliterator->slug('  --Hello, World--  ', '_'));
        self::assertSame('helloworld', $this->transliterator->slug('Hello World', ''));
        self::assertSame('', $this->transliterator->slug('北京'));
    }

    public function testResultIsAlwaysAscii(): void
    {
        $text = '';
        foreach ([[0x00A0, 0x024F], [0x0370, 0x03FF], [0x0400, 0x04FF], [0x10D0, 0x10FA], [0x1E00, 0x1EFF], [0x2000, 0x206F]] as [$from, $to]) {
            for ($codePoint = $from; $codePoint <= $to; ++$codePoint) {
                $text .= mb_chr($codePoint);
            }
        }

        self::assertMatchesRegularExpression('/^[\x00-\x7F]*$/', $this->transliterator->transliterate($text));
    }
}
