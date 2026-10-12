<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

use Closure;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\GeneratorInterface;

/**
 * Immutable chain of strategies applied to value generation. First added strategy is the outermost one:
 *
 *   $generator->strategy()->chance(30)->unique('users.email')->generate(fn (Quidam $g) => $g->ext(...)->email());
 *
 * Here chance runs first - in 70% of calls returns null without generating, so unique isn't even asked.
 * Runs on its own - the generator-wide strategy (Quidam::withStrategy()) isn't applied.
 *
 * @template-covariant TGenerator of GeneratorInterface
 * @template-covariant TShortCircuit
 */
final readonly class StrategyChain
{
    /**
     * @param TGenerator $generator
     * @param list<StrategyInterface> $strategies
     * @param list<TShortCircuit> $shortCircuitValues Values chance() can return instead of generated one (for static analysis)
     */
    public function __construct(
        private GeneratorInterface $generator,
        private RandomizerInterface $randomizer,
        private UniqueMemory $memory,
        private array $strategies = [],
        private array $shortCircuitValues = [],
    ) {
    }

    /**
     * Value different from all values returned before under the same key (memory of the generator, shared by forks)
     *
     * @return self<TGenerator, TShortCircuit>
     */
    public function unique(string $key, int $retries = UniqueStrategy::DEFAULT_RETRIES): self
    {
        return $this->add(new UniqueStrategy($this->memory, $key, $retries));
    }

    /**
     * Value accepted by the validator
     *
     * @param Closure $validator Called with generated value, valid when returns true
     * @return self<TGenerator, TShortCircuit>
     */
    public function valid(Closure $validator, int $retries = ValidStrategy::DEFAULT_RETRIES): self
    {
        return $this->add(new ValidStrategy($validator, $retries));
    }

    /**
     * Generated value only with given chance, $default otherwise
     *
     * @param int|float $chance int as percent (0-100), float as probability (0.0-1.0)
     * @param TDefault $default
     * @return self<TGenerator, TShortCircuit|TDefault>
     *
     * @template TDefault
     */
    public function chance(int|float $chance, mixed $default = null): self
    {
        return new self(
            $this->generator,
            $this->randomizer,
            $this->memory,
            [...$this->strategies, new ChanceStrategy($chance, $this->randomizer, $default)],
            [...$this->shortCircuitValues, $default],
        );
    }

    /**
     * Adds own strategy. If it short circuits, the value should be of generated type (it's not tracked by types).
     *
     * @return self<TGenerator, TShortCircuit>
     */
    public function add(StrategyInterface $strategy): self
    {
        return new self($this->generator, $this->randomizer, $this->memory, [...$this->strategies, $strategy], $this->shortCircuitValues);
    }

    /**
     * Runs strategies around the generator and returns the value
     *
     * @param Closure(TGenerator): T $generator
     * @param string $name Passed to strategies as generated value name (unique() has its own key)
     * @return T|TShortCircuit
     *
     * @template T
     */
    public function generate(Closure $generator, string $name = ''): mixed
    {
        $instance = $this->generator;
        $result = new CompositeStrategy($this->strategies)->generate($name, static fn (): mixed => $generator($instance));

        return $result instanceof ShortCircuitResult ? $result->value : $result;
    }

    /** @return list<StrategyInterface> */
    public function strategies(): array
    {
        return $this->strategies;
    }

    /**
     * Values chance() strategies can return instead of generated value
     *
     * @return list<TShortCircuit>
     */
    public function shortCircuitValues(): array
    {
        return $this->shortCircuitValues;
    }
}
