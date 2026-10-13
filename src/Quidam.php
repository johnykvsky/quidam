<?php

declare(strict_types = 1);

namespace Quidam;

use Closure;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Quidam\Container\ContainerFactoryInterface;
use Quidam\Container\QuidamContainerInterface;
use Quidam\Container\SimpleContainerFactory;
use Quidam\Core\Randomizer\Seed;
use Quidam\DeadLanguagePack\AncientLanguages;
use Quidam\DefinitionPack\DefinitionPack;
use Quidam\DefinitionPack\DefinitionPackInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Quidam\Exception\ContainerException;
use Quidam\Exception\DefinitionNotFoundException;
use Quidam\Exception\InvalidArgumentException;
use Quidam\ProviderPack\ProviderPackInterface;
use Quidam\Sequence\SequenceMemory;
use Quidam\Strategy\ShortCircuitResult;
use Quidam\Strategy\StrategyChain;
use Quidam\Strategy\StrategyInterface;
use Quidam\Strategy\UniqueMemory;
use Quidam\Strategy\UniqueStrategy;
use Quidam\Strategy\ValidStrategy;

/**
 * Entry point. Extensions live in a container under their interfaces, are created lazily and shared within
 * given instance:
 *
 *   $generator = Quidam::create();
 *   $generator->person()->name();   // typed shortcut of $generator->person()->name()
 *
 * Immutable - with*() return new instance with fresh extensions.
 */
readonly class Quidam implements GeneratorInterface
{
    use ExtensionAccessorsTrait;

    final public function __construct(
        protected QuidamContainerInterface $container,
    ) {
    }

    /**
     * @param int|null $seed When given, the same seed (and clock) produce the same data on every run
     * @param ClockInterface|null $clock Clock used as "now" for dates and time-based UUIDs
     * @param DefinitionPackInterface|null $definitionPack Default definitions, DefinitionPack when null
     * @param ContainerFactoryInterface|null $containerFactory Built-in SimpleContainerFactory when null, or an adapter
     *                                                         of another container
     */
    public static function create(
        ?int $seed = null,
        ?ClockInterface $clock = null,
        ?DefinitionPackInterface $definitionPack = null,
        ?ContainerFactoryInterface $containerFactory = null,
    ): static {
        $definitions = $definitionPack?->all() ?? self::defaultDefinitions();
        $definitions[Seed::class] = new Seed($seed);

        if ($clock !== null) {
            $definitions[ClockInterface::class] = $clock;
        }

        return new static(($containerFactory ?? new SimpleContainerFactory())->create($definitions));
    }

    /**
     * Definitions of the default DefinitionPack, built once per process - they are class names, static closures and
     * immutable objects, so every generator can start from the same array (containers don't share instances)
     *
     * @return array<string, mixed>
     */
    private static function defaultDefinitions(): array
    {
        /** @var array<string, mixed>|null $definitions */
        static $definitions = null;

        return $definitions ??= new DefinitionPack()->all();
    }

    /**
     * Instance using SecureRandomizerInterface as randomizer (by default Random\Engine\Secure) - for values which must
     * be unpredictable, i.e. passwords or tokens of real test accounts on shared environments. Slower (a system call
     * per random value), never repeatable. Forks and with*() keep it secure.
     *
     * Point SecureRandomizerInterface to own implementation (definition pack or withDefinition() before calling
     * secure() on an instance) to change it.
     *
     * @param ClockInterface|null $clock Clock used as "now" for dates and time-based UUIDs
     */
    public static function createSecure(
        ?ClockInterface $clock = null,
        ?DefinitionPackInterface $definitionPack = null,
        ?ContainerFactoryInterface $containerFactory = null,
    ): static {
        return static::create(clock: $clock, definitionPack: $definitionPack, containerFactory: $containerFactory)->secure();
    }

    /**
     * This instance switched to SecureRandomizerInterface - whatever it points to in this container
     */
    public function secure(): static
    {
        return $this->withDefinition(
            RandomizerInterface::class,
            static fn (SecureRandomizerInterface $randomizer): SecureRandomizerInterface => $randomizer,
        );
    }

    /**
     * This instance speaking a random ancient language - one of the historical packs of AncientLanguages::PACKS
     * (Akkadian, Latin, Old Norse...), picked with this instance's randomizer, so the same seed picks the same one:
     *
     *   $generator->ancient()->person()->name();   // 'Quintus Tullius Scipio', 'Leifr Eiríksson'...
     *
     * For a given language, Klingon or any other pack use withProvider().
     */
    public function ancient(): static
    {
        /** @var class-string<ProviderPackInterface> $providerPack */
        $providerPack = $this->random()->randomElement(AncientLanguages::PACKS);

        return $this->withProvider(new $providerPack());
    }

    public function withDefinition(string $id, mixed $definition): static
    {
        return $this->withDefinitions([$id => $definition]);
    }

    public function withDefinitions(array $definitions): static
    {
        return new static($this->container->withDefinitions($definitions));
    }

    public function withProvider(ProviderPackInterface $providerPack): static
    {
        return $this->withDefinitions($providerPack->all());
    }

    public function withStrategy(StrategyInterface|string $strategy): static
    {
        if (is_string($strategy) && !is_subclass_of($strategy, StrategyInterface::class)) {
            throw new InvalidArgumentException(sprintf('Strategy "%s" is not a class implementing %s', $strategy, StrategyInterface::class));
        }

        return $this->withDefinition(StrategyInterface::class, $strategy);
    }

    /**
     * Returns new instance with own random stream, seeded from this instance seed and $key - i.e. one per record:
     *   $generator->fork('users.' . $id)->person()->name()
     *
     * The same seed and key always give the same values, no matter what was generated before - adding a field or
     * a record doesn't change other records. Without seed the fork is random too. Extensions are fresh (like with*()),
     * unique and sequence memory is shared with this instance, so unique values stay unique across forks.
     */
    public function fork(string|int $key): static
    {
        $key = (string) $key;

        if (trim($key) === '') {
            throw new InvalidArgumentException('Fork key cannot be empty');
        }

        return $this->withDefinitions([
            Seed::class => $this->ext(Seed::class)->derive($key),
            UniqueMemory::class => $this->ext(UniqueMemory::class),
            SequenceMemory::class => $this->ext(SequenceMemory::class),
        ]);
    }

    public function ext(string $id): object
    {
        try {
            $extension = $this->container->get($id);
        } catch (NotFoundExceptionInterface $e) {
            throw new DefinitionNotFoundException(sprintf('No definition with id "%s" and it cannot be autowired', $id), 0, $e);
        } catch (ContainerExceptionInterface $e) {
            throw new ContainerException(sprintf('Entry "%s" cannot be built: %s', $id, $e->getMessage()), 0, $e);
        }

        if (!$extension instanceof $id) {
            throw new ContainerException(sprintf(
                'Entry "%s" resolved to %s, which is not an instance of it.',
                $id,
                get_debug_type($extension),
            ));
        }

        return $extension;
    }

    public function random(): RandomizerInterface
    {
        return $this->ext(RandomizerInterface::class);
    }

    public function generate(string $name, Closure $generator): mixed
    {
        $result = $this->ext(StrategyInterface::class)->generate($name, fn (): mixed => $generator($this));

        return $result instanceof ShortCircuitResult ? $result->value : $result;
    }

    /**
     * Chain of strategies to mix them - first added is the outermost one:
     *   $generator->strategy()->chance(30)->unique('users.phone')->generate(fn (Quidam $g) => ...)
     *
     * @return StrategyChain<static, never>
     */
    public function strategy(): StrategyChain
    {
        return new StrategyChain($this, $this->random(), $this->ext(UniqueMemory::class));
    }

    /**
     * Value different from all values returned before under the same key, i.e. for unique DB columns:
     *   $generator->unique('users.email', fn (Quidam $g) => $g->internet()->email())
     *
     * Key is the scope - all calls with the same key share memory of used values (within this instance and its forks,
     * with*() starts with empty memory). Throws OverflowException when no new value is found in $retries attempts.
     */
    public function unique(string $key, Closure $generator, int $retries = UniqueStrategy::DEFAULT_RETRIES): mixed
    {
        return $this->strategy()->unique($key, $retries)->generate($generator);
    }

    /**
     * Value accepted by the validator:
     *   $generator->valid(fn (Quidam $g) => $g->number()->randomDigit(), fn (int $d) => $d % 2 === 0)
     *
     * Throws OverflowException when no valid value is found in $retries attempts.
     */
    public function valid(Closure $generator, Closure $validator, int $retries = ValidStrategy::DEFAULT_RETRIES): mixed
    {
        return $this->strategy()->valid($validator, $retries)->generate($generator);
    }

    /**
     * Returns $callback result with given chance, null otherwise, e.g. for nullable columns:
     *   $generator->optional(fn (Quidam $g) => $g->lorem()->sentence(), 30)
     */
    public function optional(callable $callback, int|float $chance = 50): mixed
    {
        return $this->random()->getBool($chance) ? $callback($this) : null;
    }

    /**
     * List of $count values, callback receives this instance and index (0-based):
     *   $generator->many(10, fn (Quidam $g, int $i) => ['id' => $i + 1, 'name' => $g->ext(...)->name()])
     */
    public function many(int $count, Closure $generator): array
    {
        if ($count < 0) {
            throw new InvalidArgumentException('$count cannot be negative');
        }

        $values = [];
        for ($i = 0; $i < $count; ++$i) {
            $values[] = $generator($this, $i);
        }

        return $values;
    }

    /**
     * Next number of a counter, i.e. for logins or invoice numbers: sprintf('user%d', $generator->sequence('users'))
     *
     * Every key counts separately: $start on first call, then +1 on every call. Memory belongs to this instance
     * (with*() starts again, forks share it).
     */
    public function sequence(string $key, int $start = 1): int
    {
        return $this->ext(SequenceMemory::class)->next($key, $start);
    }

    /**
     * Forgets counter of given key, or all counters when null
     */
    public function resetSequence(?string $key = null): void
    {
        $this->ext(SequenceMemory::class)->reset($key);
    }

    /**
     * Forgets values remembered by unique() for given key, or all keys when null
     */
    public function resetUnique(?string $key = null): void
    {
        $this->ext(UniqueMemory::class)->reset($key);
    }

    /**
     * Container of this instance - for frameworks and tests
     */
    public function container(): QuidamContainerInterface
    {
        return $this->container;
    }
}
