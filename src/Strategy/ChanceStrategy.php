<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

use Quidam\Core\Randomizer\XoshiroRandomizer;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Generates value only with given chance, otherwise returns default (inner strategies don't run)
 */
class ChanceStrategy implements ShortCircuitStrategyInterface
{
    protected readonly RandomizerInterface $randomizer;

    /**
     * @param int|float $chance int as percent (0-100), float as probability (0.0-1.0)
     * @param RandomizerInterface|null $randomizer Pass generator's one (Quidam::random()) for seeded results
     */
    public function __construct(
        protected readonly int|float $chance,
        ?RandomizerInterface $randomizer = null,
        protected readonly mixed $default = null,
    ) {
        $valid = is_int($chance) ? $chance >= 0 && $chance <= 100 : $chance >= 0.0 && $chance <= 1.0;

        if (!$valid) {
            throw new InvalidArgumentException('Chance must be within 0..100 (int percent) or 0.0..1.0 (float probability)');
        }

        $this->randomizer = $randomizer ?? new XoshiroRandomizer();
    }

    public function generate(string $name, callable $callback): mixed
    {
        return $this->randomizer->getBool($this->chance) ? $callback() : $this->default;
    }
}
