<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

/**
 * Strategies applied one inside another - first one is the outermost:
 *
 *   new CompositeStrategy([new ChanceStrategy(70), new UniqueStrategy()])
 *
 * Here chance runs first - in 30% of calls returns null without generating, so unique isn't even asked.
 */
class CompositeStrategy implements StrategyInterface
{
    /** @var list<StrategyInterface> */
    protected readonly array $strategies;

    /** @param array<StrategyInterface> $strategies */
    public function __construct(array $strategies)
    {
        $this->strategies = array_values($strategies);
    }

    /** @return list<StrategyInterface> */
    public function strategies(): array
    {
        return $this->strategies;
    }

    /**
     * New composite with $strategy added as the innermost one
     */
    public function with(StrategyInterface $strategy): self
    {
        return new self([...$this->strategies, $strategy]);
    }

    /**
     * Returns ShortCircuitResult when a strategy short circuited - Quidam::generate() and StrategyChain unwrap it
     */
    public function generate(string $name, callable $callback): mixed
    {
        return $this->run(0, $name, $callback);
    }

    /** @param callable(): mixed $callback */
    protected function run(int $index, string $name, callable $callback): mixed
    {
        if (!isset($this->strategies[$index])) {
            return $callback();
        }

        $strategy = $this->strategies[$index];
        $called = false;
        $result = $strategy->generate($name, function () use (&$called, $index, $name, $callback): mixed {
            $called = true;

            return $this->run($index + 1, $name, $callback);
        });

        if (!$called && $strategy instanceof ShortCircuitStrategyInterface && !$result instanceof ShortCircuitResult) {
            return new ShortCircuitResult($result);
        }

        return $result;
    }
}
