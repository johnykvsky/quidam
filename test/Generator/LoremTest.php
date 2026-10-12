<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class LoremTest extends GeneratorTestCase
{
    public function testWordsAndSentences(): void
    {
        $lorem = $this->dummy->ext(LoremExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z]+$/', $lorem->word(...));
        self::assertCount(5, $lorem->words(5));
        $this->assertAlwaysMatches('/^[A-Z][a-z]*( [a-z]+){3}\.$/', static fn (): string => $lorem->sentence(4, false));
        $this->assertAlwaysMatches('/^[A-Z][a-z ]+\.$/', static fn (): string => $lorem->sentence());
        self::assertCount(2, $lorem->sentences(2));
        $this->assertAlwaysMatches('/^([A-Z][a-z ]+\. ?){2}$/', static fn (): string => $lorem->paragraph(2, false));
        self::assertCount(2, $lorem->paragraphs(2));
    }

    /** @return iterable<array{int}> */
    public static function textLengths(): iterable
    {
        yield [5];
        yield [24];
        yield [25];
        yield [60];
        yield [100];
        yield [500];
    }

    #[DataProvider('textLengths')]
    public function testTextLength(int $maxCharacters): void
    {
        $lorem = $this->dummy->ext(LoremExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $text = $lorem->text($maxCharacters);
            self::assertLessThanOrEqual($maxCharacters, mb_strlen($text));
            self::assertMatchesRegularExpression('/^[A-Z]([a-z ]*[a-z])?\.( [A-Z]([a-z ]*[a-z])?\.)*$/', $text);
        }
    }

    public function testInvalidArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(LoremExtensionInterface::class)->text(4);
    }
}
