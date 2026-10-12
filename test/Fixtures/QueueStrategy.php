<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Strategy\ShortCircuitStrategyInterface;

/**
 * Custom short circuiting strategy (DummyGenerator style - returns plain value, marked by interface) - returns queued
 * values first, then generates
 */
final class QueueStrategy implements ShortCircuitStrategyInterface
{
    /** @param list<mixed> $queue */
    public function __construct(
        private array $queue,
    ) {
    }

    public function generate(string $name, callable $callback): mixed
    {
        if ($this->queue !== []) {
            return array_shift($this->queue);
        }

        return $callback();
    }
}
