<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Addon\Text;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\PlText;
use Quidam\Test\Fixtures\TinyText;
use Quidam\Test\Generator\GeneratorTestCase;
use ReflectionClassConstant;
use ReflectionProperty;

final class TextTest extends GeneratorTestCase
{
    /** Adam Mickiewicz, "Pan Tadeusz" (1834, public domain) - different fragment than PlText */
    private const string POLISH = <<<'TEXT'
        Śród takich pól przed laty, nad brzegiem ruczaju, na pagórku niewielkim, we brzozowym gaju, stał dwór szlachecki,
        z drzewa, lecz podmurowany. Świeciły się z daleka pobielane ściany, tym bielsze, że odbite od ciemnej zieleni
        topoli, co go bronią od wiatrów jesieni. Dóm mieszkalny niewielki, lecz zewsząd chędogi, i stodołę miał wielką, i
        przy niej trzy stogi użątku, co pod strzechą zmieścić się nie może. Widać, że okolica obfita we zboże.
        TEXT;

    /** @return iterable<string, array{int, int, int}> */
    public static function ranges(): iterable
    {
        yield 'default' => [50, 200, 2];
        yield 'short' => [10, 40, 2];
        yield 'long' => [500, 1000, 2];
        yield 'random' => [50, 200, 1];
        yield 'close to corpus' => [50, 200, 4];
        yield 'max index' => [50, 300, 5];
    }

    #[DataProvider('ranges')]
    public function testRealText(int $min, int $max, int $indexSize): void
    {
        $text = $this->dummy->ext(TextInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $result = $text->realText($min, $max, $indexSize);
            $length = mb_strlen($result);

            self::assertGreaterThanOrEqual($min, $length, $result);
            self::assertLessThanOrEqual($max, $length, $result);
            self::assertMatchesRegularExpression('/^["“‘(\[]?\p{Lu}/u', $result, 'starts at sentence start');
            self::assertMatchesRegularExpression('/[.!?]["\'’”)\]]*$/u', $result, 'ends as sentence');
            self::assertSame(substr_count($result, '('), substr_count($result, ')'), $result);
            self::assertSame(0, substr_count($result, '"') % 2, $result);
            self::assertLessThanOrEqual((int) preg_match_all('/’(?!\p{L})/u', $result), substr_count($result, '‘'), $result);
        }
    }

    public function testTextIsVaried(): void
    {
        $texts = [];
        for ($i = 0; $i < 100; ++$i) {
            $texts[$this->dummy->ext(TextInterface::class)->realText()] = true;
        }

        self::assertGreaterThan(90, count($texts));
    }

    public function testSeedGivesSameText(): void
    {
        self::assertSame(
            Quidam::create(seed: 3)->ext(TextInterface::class)->realText(),
            Quidam::create(seed: 3)->ext(TextInterface::class)->realText(),
        );
    }

    public function testOwnCorpus(): void
    {
        $text = Quidam::create()->withDefinition(TextInterface::class, PlText::class)->ext(TextInterface::class);
        $polishWords = ['Litwo', 'Ojczyzno', 'zdrowie', 'Ile', 'Dziś', 'Panno', 'Ty', 'Jak', 'Gdy', 'Tymczasem'];

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $result = $text->realText(20, 150, 1);
            self::assertMatchesRegularExpression('/^(' . implode('|', $polishWords) . ')\b/u', $result);
        }
    }

    public function testUnpairedBracketsAndQuotesAreRemoved(): void
    {
        $text = Quidam::create()->ext(TinyText::class);

        for ($i = 0; $i < 200; ++$i) {
            $result = $text->realText(10, 60, 1);
            self::assertSame(substr_count($result, '('), substr_count($result, ')'), $result);
            self::assertSame(0, substr_count($result, '"') % 2, $result);
            // no unpaired opening quote - unpaired closing ’ is kept, it may be a plural possessive
            self::assertLessThanOrEqual((int) preg_match_all('/’(?!\p{L})/u', $result), substr_count($result, '‘'), $result);
            self::assertStringNotContainsString('  ', $result);

            if (str_contains($result, 'won')) {
                self::assertStringContainsString('won’t', $result, 'apostrophe is kept');
            }
        }
    }

    public function testTooSmallCorpusForRange(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Text of 500-600 characters not generated');
        Quidam::create()->ext(TinyText::class)->realText(500, 600, 2);
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function invalidArguments(): iterable
    {
        yield 'min zero' => [0, 100, 2];
        yield 'max too small' => [1, 9, 2];
        yield 'min above max' => [101, 100, 2];
        yield 'index zero' => [10, 100, 0];
        yield 'index too big' => [10, 100, 6];
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidArguments(int $min, int $max, int $indexSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(TextInterface::class)->realText($min, $max, $indexSize);
    }

    public function testModelIsSharedBetweenInstances(): void
    {
        Text::clearCache();

        Quidam::create()->ext(TextInterface::class)->realText();
        Quidam::create()->ext(TextInterface::class)->realText();
        Quidam::create()->ext(TextInterface::class)->realText(indexSize: 3);

        // one corpus - one cache entry, with models for index sizes 2 and 3
        self::assertCount(1, self::models());
        $models = array_values(self::models())[0];
        self::assertIsArray($models);
        self::assertSame([2, 3], array_keys($models));
    }

    public function testEveryCorpusHasOwnModel(): void
    {
        foreach ([[0, 1, 2, 3], [3, 2, 1, 0], [1, 3, 0, 2]] as $order) {
            Text::clearCache();
            $dummy = Quidam::create();
            $texts = [
                [$dummy->ext(TextInterface::class), self::corpus(Text::class)],
                [$dummy->ext(PlText::class), self::corpus(PlText::class)],
                [$dummy->ext(TextInterface::class)->withCorpus(self::POLISH), self::POLISH],
                [
                    $dummy->withDefinition(TextInterface::class, static fn (RandomizerInterface $randomizer): Text => new Text($randomizer, strtoupper(self::corpus(Text::class))))->ext(TextInterface::class),
                    strtoupper(self::corpus(Text::class)),
                ],
            ];

            foreach ($order as $index) {
                [$text, $corpus] = $texts[$index];
                for ($i = 0; $i < 10; ++$i) {
                    self::assertSame([], array_diff(self::words($text->realText(20, 150, 1)), self::words($corpus)), 'text ' . $index . ' uses only its own corpus');
                }
            }

            self::assertCount(4, self::models());
        }
    }

    public function testSameCorpusSharesModel(): void
    {
        Text::clearCache();
        $dummy = Quidam::create();

        $dummy->ext(PlText::class)->realText(20, 150, 1);
        $dummy->ext(TextInterface::class)->withCorpus(self::corpus(PlText::class))->realText(20, 150, 1);

        self::assertCount(1, self::models());
    }

    public function testCacheDoesNotChangeSeededText(): void
    {
        Text::clearCache();
        $cold = Quidam::create(seed: 8)->ext(TextInterface::class)->realText();

        Quidam::create()->ext(PlText::class)->realText(20, 150, 1);
        $warm = Quidam::create(seed: 8)->ext(TextInterface::class)->realText();

        self::assertSame($cold, $warm);
    }

    public function testWithCorpusLeavesOriginalUnchanged(): void
    {
        $english = $this->dummy->ext(TextInterface::class);
        $polish = $english->withCorpus(self::POLISH);

        self::assertNotSame($english, $polish);
        self::assertSame($english, $this->dummy->ext(TextInterface::class));
        self::assertSame([], array_diff(self::words($english->realText()), self::words(self::corpus(Text::class))));
    }

    public function testEmptyCorpus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text corpus cannot be empty');
        $this->dummy->ext(TextInterface::class)->withCorpus(" \n ");
    }

    public function testClearCache(): void
    {
        $this->dummy->ext(TextInterface::class)->realText();
        Text::clearCache();

        self::assertSame([], self::models());
        self::assertNotSame('', $this->dummy->ext(TextInterface::class)->realText(), 'rebuilt on next use');
    }

    /** @return array<mixed> */
    private static function models(): array
    {
        $models = new ReflectionProperty(Text::class, 'models')->getValue();
        self::assertIsArray($models);

        return $models;
    }

    /** @param class-string<Text> $class */
    private static function corpus(string $class): string
    {
        $corpus = new ReflectionClassConstant($class, 'CORPUS')->getValue();
        self::assertIsString($corpus);

        return $corpus;
    }

    /** @return list<string> unique words without punctuation, lowercase */
    private static function words(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}’\']+/u', mb_strtolower($text), $matches);

        return array_values(array_unique($matches[0]));
    }
}
