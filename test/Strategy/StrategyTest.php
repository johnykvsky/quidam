<?php

declare(strict_types = 1);

namespace Quidam\Test\Strategy;

use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;
use Quidam\Quidam;
use Quidam\Strategy\ChanceStrategy;
use Quidam\Strategy\CompositeStrategy;
use Quidam\Strategy\ShortCircuitResult;
use Quidam\Strategy\SimpleStrategy;
use Quidam\Strategy\StrategyInterface;
use Quidam\Strategy\UniqueMemory;
use Quidam\Strategy\UniqueStrategy;
use Quidam\Strategy\ValidStrategy;
use Quidam\Test\Fixtures\FixedPerson;
use Quidam\Test\Fixtures\NameRecordingStrategy;
use Quidam\Test\Fixtures\PrefixStrategy;
use Quidam\Test\Fixtures\QueueStrategy;

final class StrategyTest extends TestCase
{
    private Quidam $dummy;

    protected function setUp(): void
    {
        $this->dummy = Quidam::create();
    }

    public function testUniqueReturnsEveryValueOnce(): void
    {
        $digits = [];
        for ($i = 0; $i < 10; ++$i) {
            $digits[] = $this->dummy->unique('digit', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
        }

        sort($digits);
        self::assertSame(range(0, 9), $digits);
    }

    public function testUniqueThrowsWhenValuesRunOut(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $this->dummy->unique('digit', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
        }

        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Unique value for "digit" not found after 50 retries (10 unique values generated)');
        $this->dummy->unique('digit', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit(), 50);
    }

    public function testSameKeySharesMemoryAcrossCallSites(): void
    {
        $values = [];
        for ($i = 0; $i < 5; ++$i) {
            $values[] = $this->dummy->unique('shared', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->numberBetween(1, 10));
            $values[] = $this->dummy->unique('shared', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->numberBetween(1, 10));
        }

        sort($values);
        self::assertSame(range(1, 10), $values);
    }

    public function testDifferentKeysAreIndependent(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $this->dummy->unique('first', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
            $this->dummy->unique('second', static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
        }

        $memory = $this->dummy->ext(UniqueMemory::class);
        self::assertSame(10, $memory->count('first'));
        self::assertSame(10, $memory->count('second'));
    }

    public function testResetUnique(): void
    {
        $memory = $this->dummy->ext(UniqueMemory::class);
        $this->dummy->unique('a', static fn (): int => 1);
        $this->dummy->unique('b', static fn (): int => 1);

        $this->dummy->resetUnique('a');
        self::assertSame(0, $memory->count('a'));
        self::assertSame(1, $memory->count('b'));
        self::assertSame(1, $this->dummy->unique('a', static fn (): int => 1));

        $this->dummy->resetUnique();
        self::assertSame(0, $memory->count('a'));
        self::assertSame(0, $memory->count('b'));
    }

    public function testWithStartsWithEmptyMemory(): void
    {
        $this->dummy->unique('one', static fn (): string => 'value');

        $copy = $this->dummy->withDefinition(PersonExtensionInterface::class, FixedPerson::class);

        self::assertSame('value', $copy->unique('one', static fn (): string => 'value'));
        self::assertNotSame($this->dummy->ext(UniqueMemory::class), $copy->ext(UniqueMemory::class));
    }

    public function testUniqueComparesStrictly(): void
    {
        self::assertSame(1, $this->dummy->unique('mixed', static fn (): int => 1));
        self::assertSame('1', $this->dummy->unique('mixed', static fn (): string => '1'));
        self::assertSame(1.0, $this->dummy->unique('mixed', static fn (): float => 1.0));
    }

    public function testValid(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $even = $this->dummy->valid(
                static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit(),
                static fn (int $digit): bool => $digit % 2 === 0,
            );
            self::assertSame(0, $even % 2);
        }
    }

    public function testValidThrowsWhenNothingIsValid(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Valid value for "" not found after 20 retries');
        $this->dummy->valid(static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit(), static fn (int $digit): bool => $digit > 9, 20);
    }

    public function testChanceShortCircuitsInnerStrategies(): void
    {
        $memory = $this->dummy->ext(UniqueMemory::class);
        $calls = 0;
        $generator = static function (Quidam $d) use (&$calls): int {
            ++$calls;

            return $d->ext(NumberExtensionInterface::class)->randomDigit();
        };

        // chance outer: unique and generator never run
        for ($i = 0; $i < 20; ++$i) {
            self::assertNull($this->dummy->strategy()->chance(0)->unique('digit')->generate($generator));
        }

        self::assertSame(0, $calls);
        self::assertSame(0, $memory->count('digit'));

        // chance inner: unique passes short circuit through and doesn't remember it
        for ($i = 0; $i < 20; ++$i) {
            self::assertNull($this->dummy->strategy()->unique('digit')->chance(0)->generate($generator));
        }

        self::assertSame(0, $memory->count('digit'));
    }

    public function testChanceWithUniqueAndValid(): void
    {
        $chain = $this->dummy->strategy()
            ->chance(50)
            ->unique('even')
            ->valid(static fn (int $digit): bool => $digit % 2 === 0);

        $values = [];
        while (count(array_filter($values, static fn (?int $value): bool => $value !== null)) < 5) {
            $values[] = $chain->generate(static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
        }

        $generated = array_values(array_filter($values, static fn (?int $value): bool => $value !== null));
        sort($generated);
        self::assertSame([0, 2, 4, 6, 8], $generated);

        // chance short circuits sometimes - checked on many draws, so it's not flaky
        $chance = $this->dummy->strategy()->chance(50);
        $draws = [];
        for ($i = 0; $i < 200; ++$i) {
            $draws[] = $chance->generate(static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit());
        }

        self::assertContains(null, $draws);
        self::assertNotSame([], array_filter($draws, static fn (?int $value): bool => $value !== null));
    }

    public function testChanceDefaultAndAlways(): void
    {
        self::assertSame('n/a', $this->dummy->strategy()->chance(0, 'n/a')->generate(static fn (): string => 'value'));
        self::assertSame('value', $this->dummy->strategy()->chance(100, 'n/a')->generate(static fn (): string => 'value'));
        self::assertSame('value', $this->dummy->strategy()->chance(1.0)->generate(static fn (): string => 'value'));
    }

    public function testChainIsImmutable(): void
    {
        $base = $this->dummy->strategy();
        $unique = $base->unique('key');

        self::assertCount(0, $base->strategies());
        self::assertCount(1, $unique->strategies());
        self::assertCount(2, $unique->chance(50)->strategies());
        self::assertCount(1, $unique->strategies());
    }

    public function testCustomStrategies(): void
    {
        $value = $this->dummy->strategy()
            ->add(new PrefixStrategy('TEST-'))
            ->generate(static fn (): string => 'value');
        self::assertSame('TEST-value', $value);

        $queue = new QueueStrategy(['first', 'second']);
        $chain = $this->dummy->strategy()->unique('queued')->add($queue);
        $generator = static fn (Quidam $d): string => $d->ext(LoremExtensionInterface::class)->word();

        self::assertSame('first', $chain->generate($generator));
        self::assertSame('second', $chain->generate($generator));
        self::assertNotContains($chain->generate($generator), ['first', 'second']);
        // short circuited values are not remembered as unique
        self::assertSame(1, $this->dummy->ext(UniqueMemory::class)->count('queued'));
    }

    public function testOrderMatters(): void
    {
        // prefix outer: unique sees raw values, prefix added after
        $outer = $this->dummy->strategy()->add(new PrefixStrategy('x'))->unique('order')
            ->generate(static fn (): string => 'v');
        self::assertSame('xv', $outer);
        self::assertTrue($this->dummy->ext(UniqueMemory::class)->has('order', 'v'));
    }

    public function testSeedGivesSameResults(): void
    {
        $first = Quidam::create(seed: 7);
        $second = Quidam::create(seed: 7);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame(
                $first->strategy()->chance(50)->unique('email')->generate(static fn (Quidam $d): string => $d->ext(InternetExtensionInterface::class)->email()),
                $second->strategy()->chance(50)->unique('email')->generate(static fn (Quidam $d): string => $d->ext(InternetExtensionInterface::class)->email()),
            );
        }
    }

    public function testGeneratorWideStrategyIsSimpleByDefault(): void
    {
        self::assertInstanceOf(SimpleStrategy::class, $this->dummy->ext(StrategyInterface::class));
        self::assertSame('value', $this->dummy->generate('any', static fn (): string => 'value'));
    }

    public function testGeneratorWideStrategyAsClassSharesUniqueMemory(): void
    {
        $generator = $this->dummy->withStrategy(UniqueStrategy::class);
        $digit = static fn (Quidam $d): int => $d->number()->randomDigit();

        self::assertNotSame($generator->ext(StrategyInterface::class), $generator->fork('a')->ext(StrategyInterface::class), 'built per generator');

        // generate() and unique() use the same memory
        $first = $generator->generate('digit', $digit);
        self::assertTrue($generator->ext(UniqueMemory::class)->has('digit', $first));

        // resetUnique() clears it
        $generator->many(9, static fn (Quidam $d): int => $d->generate('digit', $digit));
        $generator->resetUnique('digit');
        self::assertCount(10, $generator->many(10, static fn (Quidam $d): int => $d->generate('digit', $digit)));

        // with*() starts with empty memory - all 10 digits again
        self::assertCount(10, $generator->withDefinition('foo', new \stdClass())->many(10, static fn (Quidam $d): int => $d->generate('digit', $digit)));

        // forks share it - all 10 digits are used up
        $this->expectException(OverflowException::class);
        $generator->fork('users.1')->generate('digit', $digit);
    }

    public function testGeneratorWideStrategyNeedsStrategyClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a class implementing');
        $this->dummy->withStrategy(\stdClass::class); // @phpstan-ignore argument.type
    }

    public function testCompositeFactoryIsRepeatablePerFork(): void
    {
        $generator = Quidam::create(seed: 5)->withDefinition(
            StrategyInterface::class,
            static fn (\Quidam\Definitions\Randomizer\RandomizerInterface $random, UniqueMemory $memory): StrategyInterface => new CompositeStrategy([
                new ChanceStrategy(50, $random),
                new UniqueStrategy($memory),
            ]),
        );
        $values = static fn (Quidam $g): array => $g->many(20, static fn (Quidam $d): mixed => $d->generate('email', static fn (Quidam $e): string => $e->internet()->email()));

        // the fork's own randomizer decides the chance - the same fork key gives the same values
        self::assertSame($values($generator->fork('a')), $values(Quidam::create(seed: 5)->withDefinition(
            StrategyInterface::class,
            static fn (\Quidam\Definitions\Randomizer\RandomizerInterface $random, UniqueMemory $memory): StrategyInterface => new CompositeStrategy([
                new ChanceStrategy(50, $random),
                new UniqueStrategy($memory),
            ]),
        )->fork('a')));
    }

    public function testGeneratorWideUniqueUsesNameAsKey(): void
    {
        $unique = $this->dummy->withStrategy(new UniqueStrategy());
        $digit = static fn (Quidam $d): int => $d->ext(NumberExtensionInterface::class)->randomDigit();

        $first = $unique->many(10, static fn (Quidam $d): int => $d->generate('first', $digit));
        $second = $unique->many(10, static fn (Quidam $d): int => $d->generate('second', $digit));
        sort($first);
        sort($second);

        self::assertSame(range(0, 9), $first);
        self::assertSame(range(0, 9), $second);

        $this->expectException(OverflowException::class);
        $unique->generate('first', $digit);
    }

    public function testGeneratorWideStrategyGetsNameAndGenerator(): void
    {
        $recorder = new NameRecordingStrategy();
        $generator = $this->dummy->withStrategy($recorder);

        self::assertSame($generator, $generator->generate('users.email', static fn (Quidam $d): Quidam => $d));
        // explicit calls skip it - only generate() is wrapped
        $generator->ext(InternetExtensionInterface::class)->email();
        self::assertSame(['users.email'], $recorder->names());
    }

    public function testGeneratorWideCompositeStrategy(): void
    {
        $queue = new QueueStrategy(['queued']);
        $generator = $this->dummy->withStrategy(new CompositeStrategy([
            new ChanceStrategy(100, $this->dummy->random()),
            new UniqueStrategy(),
            $queue,
        ]));

        // short circuited by QueueStrategy - unwrapped, and not remembered by unique
        self::assertSame('queued', $generator->generate('k', static fn (): string => 'value'));
        self::assertSame('value', $generator->generate('k', static fn (): string => 'value'));

        $this->expectException(OverflowException::class);
        $generator->generate('k', static fn (): string => 'value');
    }

    public function testGeneratorWideChanceReturnsDefault(): void
    {
        $generator = $this->dummy->withStrategy(new ChanceStrategy(0, default: 'none'));

        self::assertSame('none', $generator->generate('k', static fn (): string => 'value'));
    }

    public function testCompositeStrategyFirstIsOutermost(): void
    {
        $composite = new CompositeStrategy([new PrefixStrategy('a'), new PrefixStrategy('b')]);

        self::assertSame('abv', $composite->generate('k', static fn (): string => 'v'));
        self::assertSame('abcv', $composite->with(new PrefixStrategy('c'))->generate('k', static fn (): string => 'v'));
        self::assertCount(2, $composite->strategies());

        $shortCircuit = new CompositeStrategy([new ChanceStrategy(0, default: 'd')])->generate('k', static fn (): string => 'v');
        self::assertEquals(new ShortCircuitResult('d'), $shortCircuit);
    }

    public function testChainPassesNameToStrategies(): void
    {
        $recorder = new NameRecordingStrategy();
        $this->dummy->strategy()->add($recorder)->generate(static fn (): int => 1, 'orders.number');

        self::assertSame(['orders.number'], $recorder->names());
    }

    public function testUniqueMemoryCanBeShared(): void
    {
        $memory = new UniqueMemory();
        new UniqueStrategy($memory, 'k')->generate('ignored', static fn (): int => 1);

        self::assertTrue($memory->has('k', 1));
        self::assertFalse($memory->has('ignored', 1));
    }

    public function testInvalidArguments(): void
    {
        foreach (
            [
            fn (): mixed => $this->dummy->unique('', static fn (): int => 1),
            fn (): mixed => $this->dummy->unique('key', static fn (): int => 1, 0),
            fn (): mixed => $this->dummy->valid(static fn (): int => 1, static fn (): bool => true, 0),
            fn (): mixed => $this->dummy->strategy()->chance(150),
            fn (): mixed => $this->dummy->strategy()->chance(1.5),
            fn (): mixed => new ChanceStrategy(-1, $this->dummy->random()),
            static fn (): mixed => new UniqueStrategy(key: ' '),
            static fn (): mixed => new UniqueStrategy()->generate('', static fn (): int => 1),
            static fn (): mixed => new UniqueStrategy(retries: 0),
            static fn (): mixed => new ValidStrategy(static fn (): bool => true, 0),
            ] as $call
        ) {
            try {
                $call();
                self::fail('Exception expected');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
