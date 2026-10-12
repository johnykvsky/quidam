<?php

declare(strict_types = 1);

namespace Quidam\Test\Randomizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Core\Randomizer\SecureRandomizer;
use Quidam\Core\Randomizer\XoshiroRandomizer;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer as PhpRandomizer;

final class RandomizerTest extends TestCase
{
    public function testSeedGivesSameSequence(): void
    {
        $a = new XoshiroRandomizer(10);
        $b = new XoshiroRandomizer(10);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame($a->getInt(0, 1000), $b->getInt(0, 1000));
        }
    }

    public function testRanges(): void
    {
        $randomizer = new XoshiroRandomizer();

        for ($i = 0; $i < 100; ++$i) {
            $int = $randomizer->getInt(-5, 5);
            self::assertGreaterThanOrEqual(-5, $int);
            self::assertLessThanOrEqual(5, $int);

            $float = $randomizer->getFloat(1.5, 2.5);
            self::assertGreaterThanOrEqual(1.5, $float);
            self::assertLessThanOrEqual(2.5, $float);

            self::assertMatchesRegularExpression('/^[a-z]$/', $randomizer->randomLetter());
            self::assertMatchesRegularExpression('/^[ab]{5}$/', $randomizer->getBytesFromString('ab', 5));
        }

        self::assertSame(16, strlen($randomizer->getBytes()));
    }

    public function testGetBoolChances(): void
    {
        $randomizer = new XoshiroRandomizer();

        self::assertTrue($randomizer->getBool(100));
        self::assertFalse($randomizer->getBool(0));
        self::assertTrue($randomizer->getBool(1.0));
        self::assertFalse($randomizer->getBool(0.0));
    }

    public function testRandomElementAndKey(): void
    {
        $randomizer = new XoshiroRandomizer();
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        for ($i = 0; $i < 30; ++$i) {
            self::assertContains($randomizer->randomElement($array), $array);
            self::assertArrayHasKey($randomizer->randomKey($array), $array);
        }
    }

    public function testRandomElements(): void
    {
        $randomizer = new XoshiroRandomizer();

        $unique = $randomizer->randomElements([1, 2, 3, 3, 4], 4, true);
        sort($unique);
        self::assertSame([1, 2, 3, 4], $unique);

        $many = $randomizer->randomElements([1, 2], 10);
        self::assertCount(10, $many);
        self::assertSame([], $randomizer->randomElements([1], 0));
    }

    public function testRandomKeyMatchesIndexOfSameRandomInt(): void
    {
        $arrays = [
            'list' => range(10, 300),
            'assoc' => array_combine(array_map(static fn (int $i): string => 'key' . $i, range(1, 200)), range(1, 200)),
            'sparse' => [5 => 'a', 9 => 'b', 2 => 'c', 100 => 'd'],
        ];

        foreach ($arrays as $name => $array) {
            $randomizer = new XoshiroRandomizer(123);
            $reference = new PhpRandomizer(new Xoshiro256StarStar(123));
            $keys = array_keys($array);

            for ($i = 0; $i < 200; ++$i) {
                self::assertSame($keys[$reference->getInt(0, count($keys) - 1)], $randomizer->randomKey($array), $name);
            }
        }
    }

    public function testWeightedElement(): void
    {
        $randomizer = new XoshiroRandomizer();
        $counts = ['a' => 0, 'b' => 0, 'never' => 0];

        for ($i = 0; $i < 10000; ++$i) {
            ++$counts[$randomizer->weightedElement(['a' => 80, 'b' => 20.0, 'never' => 0])];
        }

        self::assertSame(0, $counts['never']);
        self::assertGreaterThan(7500, $counts['a']);
        self::assertLessThan(8500, $counts['a']);
        self::assertSame(5, $randomizer->weightedElement([5 => 1]));
    }

    public function testShuffleKeepsElements(): void
    {
        $shuffled = (new XoshiroRandomizer())->shuffleElements(range(1, 10));
        sort($shuffled);

        self::assertSame(range(1, 10), $shuffled);
    }

    /** @return iterable<string, array{callable(RandomizerInterface): mixed}> */
    public static function invalidCalls(): iterable
    {
        yield 'element of empty' => [static fn (RandomizerInterface $r): mixed => $r->randomElement([])];
        yield 'key of empty' => [static fn (RandomizerInterface $r): mixed => $r->randomKey([])];
        yield 'elements of empty' => [static fn (RandomizerInterface $r): mixed => $r->randomElements([])];
        yield 'too many unique' => [static fn (RandomizerInterface $r): mixed => $r->randomElements([1, 1], 2, true)];
        yield 'zero bytes' => [static fn (RandomizerInterface $r): mixed => $r->getBytes(0)];
        yield 'weighted empty' => [static fn (RandomizerInterface $r): mixed => $r->weightedElement([])];
        yield 'weighted all zero' => [static fn (RandomizerInterface $r): mixed => $r->weightedElement(['a' => 0])];
        yield 'weighted negative' => [static fn (RandomizerInterface $r): mixed => $r->weightedElement(['a' => 5, 'b' => -1])];
        yield 'int min above max' => [static fn (RandomizerInterface $r): mixed => $r->getInt(5, 1)];
        yield 'float min above max' => [static fn (RandomizerInterface $r): mixed => $r->getFloat(5.0, 1.0)];
        yield 'percent above 100' => [static fn (RandomizerInterface $r): mixed => $r->getBool(150)];
        yield 'negative percent' => [static fn (RandomizerInterface $r): mixed => $r->getBool(-5)];
        yield 'probability above 1' => [static fn (RandomizerInterface $r): mixed => $r->getBool(1.5)];
        yield 'negative probability' => [static fn (RandomizerInterface $r): mixed => $r->getBool(-0.1)];
        yield 'empty pool' => [static fn (RandomizerInterface $r): mixed => $r->getBytesFromString('')];
    }

    /** @param callable(RandomizerInterface): mixed $call */
    #[DataProvider('invalidCalls')]
    public function testInvalidArguments(callable $call): void
    {
        $this->expectException(InvalidArgumentException::class);
        $call(new XoshiroRandomizer());
    }

    public function testEngines(): void
    {
        self::assertInstanceOf(SecureRandomizerInterface::class, new SecureRandomizer());
        self::assertNotSame(new XoshiroRandomizer()->getInt(0, PHP_INT_MAX), new XoshiroRandomizer()->getInt(0, PHP_INT_MAX));
        self::assertSame(new XoshiroRandomizer(5)->getInt(0, PHP_INT_MAX), new XoshiroRandomizer(5)->getInt(0, PHP_INT_MAX));
        self::assertNotSame(new SecureRandomizer()->getInt(0, PHP_INT_MAX), new SecureRandomizer()->getInt(0, PHP_INT_MAX));
    }

    public function testSecureRandomizerHasTheSameBehaviour(): void
    {
        $randomizer = new SecureRandomizer();

        self::assertContains($randomizer->randomElement(['a', 'b']), ['a', 'b']);
        self::assertSame('new', $randomizer->weightedElement(['new' => 1, 'paid' => 0]));

        $this->expectException(InvalidArgumentException::class);
        $randomizer->getInt(5, 1);
    }
}
