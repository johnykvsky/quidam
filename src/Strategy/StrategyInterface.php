<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

/**
 * Wraps value generation with additional behaviour (uniqueness, validation, chance...) - the same contract as
 * DummyGenerator strategies.
 *
 * Call $callback() to get the value from inner strategies and the generator - possibly many times (retry), or not at
 * all and return own value. A value returned without calling $callback should be marked: implement
 * ShortCircuitStrategyInterface (CompositeStrategy wraps it), or return ShortCircuitResult. Outer strategies must pass
 * ShortCircuitResult through untouched - it's the final value, not one to validate or remember.
 */
interface StrategyInterface
{
    /**
     * @param string $name Key of generated value - i.e. scope of unique values ('users.email')
     * @param callable(): mixed $callback Generates value - runs inner strategies and the generator
     * @return mixed Value, or ShortCircuitResult with the final value
     */
    public function generate(string $name, callable $callback): mixed;
}
