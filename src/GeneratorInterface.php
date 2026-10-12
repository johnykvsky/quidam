<?php

declare(strict_types = 1);

namespace Quidam;

use Closure;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\ProviderPack\ProviderPackInterface;
use Quidam\Strategy\StrategyChain;
use Quidam\Strategy\StrategyInterface;

/**
 * Generator API - no magic: values come from extensions fetched with ext(), strategies wrap explicit closures
 */
interface GeneratorInterface
{
    /**
     * Extension (or any other entry) stored in the container under given id - interface ids of default extensions:
     *   $generator->ext(PersonExtensionInterface::class)->name()
     *
     * @param class-string<T> $id
     * @return T
     *
     * @template T of object
     */
    public function ext(string $id): object;

    public function random(): RandomizerInterface;

    /**
     * Generator with definition added or replaced:
     *   ->withDefinition(PersonExtensionInterface::class, MyPerson::class)
     *   ->withDefinition(MyExtension::class, fn (PersonExtensionInterface $person) => new MyExtension($person, 'config'))
     *
     * @param mixed $definition class-string, Closure factory (parameters resolved by type) or object
     */
    public function withDefinition(string $id, mixed $definition): static;

    /** @param array<string, mixed> $definitions */
    public function withDefinitions(array $definitions): static;

    /**
     * Generator with definitions of the provider pack (i.e. language pack)
     */
    public function withProvider(ProviderPackInterface $providerPack): static;

    /**
     * Generator with generator-wide strategy used by generate(). A class name is built by the container for every
     * generator - UniqueStrategy::class then shares the generator's unique memory (unique(), resetUnique(), forks);
     * an object is kept as it is by with*() copies and forks, with its own state.
     *
     * @param StrategyInterface|class-string<StrategyInterface> $strategy
     */
    public function withStrategy(StrategyInterface|string $strategy): static;

    /**
     * Value of $generator wrapped in generator-wide strategy (withStrategy()), $name is the key passed to it:
     *   $generator->withStrategy(UniqueStrategy::class)->generate('users.email', fn ($g) => $g->internet()->email())
     *
     * @param Closure(static): T $generator
     * @return T
     *
     * @template T
     */
    public function generate(string $name, Closure $generator): mixed;

    /**
     * Chain of strategies to mix them - first added is the outermost one
     *
     * @return StrategyChain<static, never>
     */
    public function strategy(): StrategyChain;

    /**
     * @param Closure(static): T $generator
     * @return T
     *
     * @template T
     */
    public function unique(string $key, Closure $generator, int $retries = 10000): mixed;

    /**
     * @param Closure(static): T $generator
     * @param Closure(T): bool $validator
     * @return T
     *
     * @template T
     */
    public function valid(Closure $generator, Closure $validator, int $retries = 10000): mixed;

    /**
     * @param callable(static): T $callback
     * @param int|float $chance int as percent (0-100), float as probability (0.0-1.0)
     * @return T|null
     *
     * @template T
     */
    public function optional(callable $callback, int|float $chance = 50): mixed;

    /**
     * @param Closure(static, int): T $generator
     * @return list<T>
     *
     * @template T
     */
    public function many(int $count, Closure $generator): array;

    public function sequence(string $key, int $start = 1): int;

    public function resetSequence(?string $key = null): void;

    public function resetUnique(?string $key = null): void;

    public function fork(string|int $key): static;
}
